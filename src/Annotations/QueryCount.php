<?php

namespace Liip\FunctionalTestBundle\Annotations;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class QueryCount
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
