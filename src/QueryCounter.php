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

use Doctrine\Common\Annotations\Reader;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Liip\FunctionalTestBundle\Exception\AllowedQueriesExceededException;

final class QueryCounter
{
    private ?int $defaultMaxCount;
    private ?Reader $annotationReader;

    public function __construct(?int $defaultMaxCount, ?Reader $annotationReader)
    {
        $this->defaultMaxCount = $defaultMaxCount;
        $this->annotationReader = $annotationReader;
    }

    public function checkQueryCount(int $actualQueryCount): void
    {
        $maxQueryCount = $this->getMaxQueryCount();

        if (null === $maxQueryCount) {
            return;
        }

        if ($actualQueryCount > $maxQueryCount) {
            throw new AllowedQueriesExceededException("Allowed amount of queries ($maxQueryCount) exceeded (actual: $actualQueryCount).");
        }
    }

    private function getMaxQueryCount(): ?int
    {
        $maxQueryCount = $this->getMaxQueryAnnotation();

        if (null !== $maxQueryCount) {
            return $maxQueryCount;
        }

        return $this->defaultMaxCount;
    }

    private function getMaxQueryAnnotation(): ?int
    {
        if (null === $this->annotationReader) {
            @trigger_error('The annotationReader is not available', \E_USER_ERROR);
        }

        foreach (debug_backtrace() as $step) {
            if ('test' === substr($step['function'], 0, 4)) { //TODO: handle tests with the @test annotation
                $annotations = $this->annotationReader->getMethodAnnotations(
                    new \ReflectionMethod($step['class'], $step['function'])
                );

                foreach ($annotations as $annotationClass) {
                    if ($annotationClass instanceof QueryCount && isset($annotationClass->maxQueries)) {
                        /* @var $annotations \Liip\FunctionalTestBundle\Annotations\QueryCount */

                        return $annotationClass->maxQueries;
                    }
                }
            }
        }

        return null;
    }
}
