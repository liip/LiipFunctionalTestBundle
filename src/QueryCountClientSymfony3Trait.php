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

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 *
 * @internal
 */
trait QueryCountClientSymfony3Trait
{
    public function request(
        $method,
        $uri,
        array $parameters = [],
        array $files = [],
        array $server = [],
        $content = null,
        $changeHistory = true
    ) {
        $crawler = parent::request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
        $this->checkQueryCount();

        return $crawler;
    }
}
