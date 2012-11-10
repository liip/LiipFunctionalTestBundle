<?php

namespace Liip\FunctionalTestBundle\Annotations;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class QueryCount
{
    /** @var integer */
    public $maxQueries;

    public function __construct(array $values)
    {
        if (isset($values['value'])) {
            $this->maxQueries = $values['value'];
        }
    }
}
