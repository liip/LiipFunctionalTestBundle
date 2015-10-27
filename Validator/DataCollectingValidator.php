<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Validator;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataCollectingValidator implements ValidatorInterface, EventSubscriberInterface
{
    /**
     * @var ValidatorInterface
     */
    protected $wrappedValidator;

    /**
     * @var ConstraintViolationListInterface
     */
    protected $lastErrors;

    public function __construct(ValidatorInterface $wrappedValidator)
    {
        $this->wrappedValidator = $wrappedValidator;
        $this->clearLastErrors();
    }

    public function clearLastErrors()
    {
        $this->lastErrors = new ConstraintViolationList();
    }

    public function getLastErrors()
    {
        return $this->lastErrors;
    }

    public function getMetadataFor($value)
    {
        return $this->wrappedValidator->getMetadataFor($value);
    }

    public function hasMetadataFor($value)
    {
        return $this->wrappedValidator->hasMetadataFor($value);
    }

    public function validate($value, $constraints = null, $groups = null)
    {
        return $this->lastErrors = $this->wrappedValidator->validate($value, $constraints, $groups);
    }

    public function validateProperty($object, $propertyName, $groups = null)
    {
        return $this->wrappedValidator->validateProperty($object, $propertyName, $groups);
    }

    public function validatePropertyValue($objectOrClass, $propertyName, $value, $groups = null)
    {
        return $this->wrappedValidator->validatePropertyValue($objectOrClass, $propertyName, $value, $groups);
    }

    public function startContext()
    {
        return $this->wrappedValidator->startContext();
    }

    public function inContext(ExecutionContextInterface $context)
    {
        return $this->wrappedValidator->inContext($context);
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('clearLastErrors', 99999),
        );
    }
}
