<?php

declare(strict_types=1);

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Utils;

use Liip\FunctionalTestBundle\Test\ValidationErrorsConstraint;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class HttpAssertions extends TestCase
{
    /**
     * Checks the success state of a response.
     *
     * @param Response $response Response object
     * @param bool     $success  to define whether the response is expected to be successful
     */
    public static function isSuccessful(Response $response, bool $success = true, string $type = 'text/html'): void
    {
        try {
            $crawler = new Crawler();
            $crawler->addContent($response->getContent(), $type);
            if (!\count($crawler->filter('title'))) {
                $title = '['.$response->getStatusCode().'] - '.$response->getContent();
            } else {
                $title = $crawler->filter('title')->text();
            }
        } catch (\Exception $e) {
            $title = $e->getMessage();
        }

        if ($success) {
            self::assertTrue($response->isSuccessful(), 'The Response was not successful: '.$title);
        } else {
            self::assertFalse($response->isSuccessful(), 'The Response was successful: '.$title);
        }
    }

    /**
     * Asserts that the HTTP response code of the last request performed by
     * $client matches the expected code. If not, raises an error with more
     * information.
     */
    public static function assertStatusCode(int $expectedStatusCode, Client $client): void
    {
        $container = $client->getContainer();

        if ($container->has('test.service_container')) {
            $container = $container->get('test.service_container');
        }

        $helpfulErrorMessage = '';

        if ($expectedStatusCode !== $client->getResponse()->getStatusCode()) {
            // Get a more useful error message, if available
            if ($exception = $container->get('liip_functional_test.exception_listener')->getLastException()) {
                $helpfulErrorMessage = $exception->getMessage()."\n\n".$exception->getTraceAsString();
            } elseif (
                $container->has('liip_functional_test.validator') &&
                \count($validationErrors = $container->get('liip_functional_test.validator')->getLastErrors())
            ) {
                $helpfulErrorMessage = "Unexpected validation errors:\n";

                foreach ($validationErrors as $error) {
                    $helpfulErrorMessage .= sprintf("+ %s: %s\n", $error->getPropertyPath(), $error->getMessage());
                }
            } else {
                $helpfulErrorMessage = substr((string) $client->getResponse(), 0, 200);
            }
        }

        self::assertSame($expectedStatusCode, $client->getResponse()->getStatusCode(), $helpfulErrorMessage);
    }

    /**
     * Assert that the last validation errors within $container match the
     * expected keys.
     *
     * @param array $expected A flat array of field names
     */
    public static function assertValidationErrors(array $expected, ContainerInterface $container): void
    {
        if ($container->has('test.service_container')) {
            $container = $container->get('test.service_container');
        }

        if (!$container->has('liip_functional_test.validator')) {
            self::fail(sprintf(
                'Method %s() can not be used as the validation component of the Symfony framework is disabled.',
                __METHOD__
            ));
        }

        self::assertThat(
            $container->get('liip_functional_test.validator')->getLastErrors(),
            new ValidationErrorsConstraint($expected),
            'Validation errors should match.'
        );
    }
}
