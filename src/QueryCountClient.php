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

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\CompilerDebugDumpPass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

// Symfony <4 BC
if (class_exists(CompilerDebugDumpPass::class)) {
    class_alias(QueryCountClientSymfony3Trait::class, QueryCountClientTrait::class);
}

// Symfony <4.3.1 BC
if (!class_exists(KernelBrowser::class)) {
    class_alias(Client::class, KernelBrowser::class);
}

if (!class_exists(Client::class)) {
    class_alias(KernelBrowser::class, Client::class);
}

class QueryCountClient extends KernelBrowser
{
    /*
     * We use trait only because of Client::request signature strict type mismatch between Symfony 3 and 4.
     */
    use QueryCountClientTrait;

    /** @var QueryCounter */
    private $queryCounter;

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
                'See https://github.com/liip/LiipFunctionalTestBundle#query-counter'.
                "\n";
            // @codeCoverageIgnoreEnd
        }
    }
}
