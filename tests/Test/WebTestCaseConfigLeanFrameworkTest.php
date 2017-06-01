<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Tests\Test;

use Liip\FunctionalTestBundle\Test\WebTestCase;

/**
 * Test Lean Framework - with validator component disabled.
 *
 * Use Tests/AppConfigLeanFramework/AppConfigLeanFrameworkKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 */
class WebTestCaseConfigLeanFrameworkTest extends WebTestCase
{
    protected static function getKernelClass()
    {
        require_once __DIR__.'/../AppConfigLeanFramework/AppConfigLeanFrameworkKernel.php';

        return 'AppConfigLeanFrameworkKernel';
    }

    public function testAssertStatusCode()
    {
        $client = static::makeClient();

        $path = '/';
        $crawler = $client->request('GET', $path);

        $this->assertStatusCode(200, $client);
    }

    public function testAssertValidationErrorsTriggersError()
    {
        $client = static::makeClient();

        $path = '/form';
        $crawler = $client->request('GET', $path);

        try {
            $this->assertValidationErrors(array(), $client->getContainer());
        } catch (\Exception $e) {
            $this->assertSame(
                'Method Liip\FunctionalTestBundle\Utils\HttpAssertions::assertValidationErrors() can not be used as the validation component of the Symfony framework is disabled.',
                $e->getMessage()
            );

            return;
        }

        $this->fail('Test failed.');
    }
}
