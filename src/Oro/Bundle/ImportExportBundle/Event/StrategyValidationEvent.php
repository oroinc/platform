<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Build validation errors for entity event
 */
class StrategyValidationEvent extends Event
{
    public const BUILD_ERRORS = 'oro_importexport.strategy.build_errors';
    public const DELIMITER = ': ';

    /** @var ConstraintViolationListInterface */
    private $constraintViolationList;

    private $errors = [];

    public function __construct(ConstraintViolationListInterface $constraintViolationList)
    {
        $this->constraintViolationList = $constraintViolationList;
    }

    public function getErrors(): array
    {
        return array_values($this->errors);
    }

    public function addError(string $message): void
    {
        $this->errors[md5($message)] = $message;
    }

    public function removeError(string $message): void
    {
        unset($this->errors[md5($message)]);
    }

    public function getConstraintViolationList(): ConstraintViolationListInterface
    {
        return $this->constraintViolationList;
    }
}
