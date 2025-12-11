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

namespace Liip\FunctionalTestBundle;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

class QueryCountClient extends KernelBrowser
{
    /** @var QueryCounter */
    private $queryCounter;

    public function request(
        string $method,
        string $uri,
        array $parameters = [],
        array $files = [],
        array $server = [],
        ?string $content = null,
        bool $changeHistory = true
    ): Crawler {
        $crawler = parent::request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
        $this->checkQueryCount();

        return $crawler;
    }

    public function setQueryCounter(QueryCounter $queryCounter): void
    {
        $this->queryCounter = $queryCounter;
    }

    private function checkQueryCount(): void
    {
        if ($this->getProfile()) {
            $this->queryCounter->checkQueryCount(
                $this->getProfile()->getCollector('db')->getQueryCount()
            );
        } else {
            // @codeCoverageIgnoreStart
            echo "\n".
                'Profiler is disabled, it must be enabled for the '.
                'Query Counter. '.
                'See https://github.com/liip/LiipFunctionalTestBundle/blob/master/doc/query.md'.
                "\n";
            // @codeCoverageIgnoreEnd
        }
    }
}
