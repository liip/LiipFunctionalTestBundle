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

namespace Liip\FunctionalTestBundle\Validator;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\MetadataInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
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

    public function clearLastErrors(): void
    {
        $this->lastErrors = new ConstraintViolationList();
    }

    public function getLastErrors(): ConstraintViolationListInterface
    {
        return $this->lastErrors;
    }

    public function getMetadataFor($value): MetadataInterface
    {
        return $this->wrappedValidator->getMetadataFor($value);
    }

    public function hasMetadataFor($value): bool
    {
        return $this->wrappedValidator->hasMetadataFor($value);
    }

    public function validate($value, $constraints = null, $groups = null): ConstraintViolationListInterface
    {
        return $this->lastErrors = $this->wrappedValidator->validate($value, $constraints, $groups);
    }

    public function validateProperty($object, $propertyName, $groups = null): ConstraintViolationListInterface
    {
        return $this->wrappedValidator->validateProperty($object, $propertyName, $groups);
    }

    public function validatePropertyValue($objectOrClass, $propertyName, $value, $groups = null): ConstraintViolationListInterface
    {
        return $this->wrappedValidator->validatePropertyValue($objectOrClass, $propertyName, $value, $groups);
    }

    public function startContext(): ContextualValidatorInterface
    {
        return $this->wrappedValidator->startContext();
    }

    public function inContext(ExecutionContextInterface $context): ContextualValidatorInterface
    {
        return $this->wrappedValidator->inContext($context);
    }

    public function onKernelRequest(GetResponseEvent $event): void
    {
        // The isMainRequest method has been added in Symfony 5.3
        if (method_exists($event, 'isMainRequest')) {
            if ($event->isMainRequest()) {
                $this->clearLastErrors();
            }

            return;
        }

        // For Symfony < 5.3, call the legacy method
        if ($event->isMasterRequest()) {
            $this->clearLastErrors();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 99999],
        ];
    }
}
