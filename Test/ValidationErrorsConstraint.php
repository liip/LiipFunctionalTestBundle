<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Test;

// BC
if (class_exists('\PHPUnit_Framework_Constraint')) {
    class_alias('\PHPUnit_Framework_Constraint', '\PHPUnit\Framework\Constraint\Constraint');
}
if (class_exists('\PHPUnit_Framework_ExpectationFailedException')) {
    class_alias('\PHPUnit_Framework_ExpectationFailedException', '\PHPUnit\Framework\ExpectationFailedException');
}

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\Validator\ConstraintViolationList;

class ValidationErrorsConstraint extends Constraint
{
    private $expect;

    /**
     * ValidationErrorsConstraint constructor.
     *
     * @param $expect
     */
    public function __construct(array $expect)
    {
        $this->expect = $expect;
        sort($this->expect);
        parent::__construct();
    }

    /**
     * @param ConstraintViolationList $other
     * @param string                  $description
     * @param bool                    $returnResult
     *
     * @return mixed
     */
    public function evaluate($other, $description = '', $returnResult = false)
    {
        $actual = [];

        foreach ($other as $error) {
            $actual[$error->getPropertyPath()][] = $error->getMessage();
        }

        ksort($actual);

        if (array_keys($actual) === $this->expect) {
            return true;
        }

        if ($returnResult) {
            return false;
        }

        // Generate failure message
        $mismatchedKeys = array_merge(
            array_diff(array_keys($actual), $this->expect),
            array_diff($this->expect, array_keys($actual))
        );
        sort($mismatchedKeys);

        $lines = [];

        foreach ($mismatchedKeys as $key) {
            if (isset($actual[$key])) {
                foreach ($actual[$key] as $unexpectedErrorMessage) {
                    $lines[] = '+ '.$key.' ('.$unexpectedErrorMessage.')';
                }
            } else {
                $lines[] = '- '.$key;
            }
        }

        throw new ExpectationFailedException(
            $description."\n".implode("\n", $lines)
        );
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString(): string
    {
        return 'validation errors match';
    }
}
