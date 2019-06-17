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

namespace Liip\FunctionalTestBundle\Annotations;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
final class QueryCount
{
    /** @var int */
    public $maxQueries;

    public function __construct(array $values)
    {
        if (isset($values['value'])) {
            $this->maxQueries = $values['value'];
        }
    }
}
