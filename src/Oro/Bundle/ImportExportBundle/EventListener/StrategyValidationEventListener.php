<?php

namespace Oro\Bundle\ImportExportBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;
use Oro\Bundle\ImportExportBundle\Event\StrategyValidationEvent;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Build import errors from validation violation list
 */
class StrategyValidationEventListener
{
    /** @var ConfigurableTableDataConverter */
    protected $configurableDataConverter;

    public function __construct(ConfigurableTableDataConverter $configurableDataConverter)
    {
        $this->configurableDataConverter = $configurableDataConverter;
    }

    public function buildErrors(StrategyValidationEvent $event)
    {
        $violations = $event->getConstraintViolationList();

        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            if ($propertyPath && is_object($violation->getRoot())) {
                $fieldHeader = $this->configurableDataConverter->getFieldHeaderWithRelation(
                    ClassUtils::getClass($violation->getRoot()),
                    $propertyPath
                );
                $propertyPath = ($fieldHeader ?: $propertyPath).StrategyValidationEvent::DELIMITER;
            }

            $event->addError($propertyPath.$violation->getMessage());
        }
    }
}
