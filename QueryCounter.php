<?php

namespace Liip\FunctionalTestBundle;

use Doctrine\Common\Annotations\Reader;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Liip\FunctionalTestBundle\Exception\AllowedQueriesExceededException;

class QueryCounter
{
    /** @var int */
    private $defaultMaxCount;

    /** @var \Doctrine\Common\Annotations\AnnotationReader */
    private $annotationReader;

    /**
     * "query.max_query_count" is an array, it is only accessible
     * through "query" node and getting the "max_query_count" array
     * key with PHP.
     */
    public function __construct($query, Reader $annotationReader)
    {
        $this->defaultMaxCount = $query['max_query_count'];
        $this->annotationReader = $annotationReader;
    }

    public function checkQueryCount($actualQueryCount)
    {
        $maxQueryCount = $this->getMaxQueryCount();

        if (null === $maxQueryCount) {
            return;
        }

        if ($actualQueryCount > $maxQueryCount) {
            throw new AllowedQueriesExceededException(
                "Allowed amount of queries ($maxQueryCount) exceeded (actual: $actualQueryCount)."
            );
        }
    }

    private function getMaxQueryCount()
    {
        $maxQueryCount = $this->getMaxQueryAnnotation();

        if (false !== $maxQueryCount) {
            return $maxQueryCount;
        }

        return $this->defaultMaxCount;
    }

    private function getMaxQueryAnnotation()
    {
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

        return false;
    }
}
