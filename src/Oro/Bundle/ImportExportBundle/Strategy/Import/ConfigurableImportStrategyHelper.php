<?php

namespace Oro\Bundle\ImportExportBundle\Strategy\Import;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\EntityBundle\EntityProperty\DenormalizedPropertyAwareInterface;
use Oro\Bundle\ImportExportBundle\Event\StrategyValidationEvent;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Helper methods for import strategies with performance improvements:
 * - Do not override collections fully to make UoW faster
 * - Compare DateTimes by values
 * - Handle different scalar types, most of data are strings in imported data but different types in the DB.
 */
class ConfigurableImportStrategyHelper extends ImportStrategyHelper
{
    use LoggerAwareTrait;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function importEntity($databaseEntity, $importedEntity, array $excludedProperties = [])
    {
        $databaseEntityClass = $this->verifyClass($databaseEntity, $importedEntity);

        $entityProperties = $this->getEntityPropertiesByClassName($databaseEntityClass);
        $importedEntityProperties = array_diff($entityProperties, $excludedProperties);
        $classMetadata = $this
            ->getEntityManager($databaseEntityClass)
            ->getClassMetadata($databaseEntityClass);

        foreach ($importedEntityProperties as $propertyName) {
            // we should not overwrite deleted fields
            if ($this->isDeletedField($databaseEntityClass, $propertyName)) {
                continue;
            }

            $importedValue = $this->fieldHelper->getObjectValue($importedEntity, $propertyName);
            $databaseValue = $this->fieldHelper->getObjectValue($databaseEntity, $propertyName);

            if ($classMetadata->hasAssociation($propertyName)
                && $classMetadata->getAssociationMapping($propertyName)['type'] & ClassMetadataInfo::TO_MANY) {
                /** @var Collection $importedValue */
                if (!$importedValue instanceof Collection) {
                    $importedValue = $this->fieldHelper->getObjectValueWithReflection($importedEntity, $propertyName);
                }

                $this->fieldHelper->setObjectValue($databaseEntity, $propertyName, $importedValue->toArray());
                if ($databaseValue instanceof PersistentCollection && $databaseValue->isDirty()) {
                    $this->logger->debug(
                        'Property changed during import.',
                        [
                            'databaseEntityClass' => $databaseEntityClass,
                            'propertyName' => $propertyName,
                        ]
                    );
                } elseif ($databaseValue instanceof PersistentCollection && $databaseValue->isInitialized()) {
                    $this->logger->debug(
                        'Property initialized but not changed during import.',
                        [
                            'databaseEntityClass' => $databaseEntityClass,
                            'propertyName' => $propertyName,
                        ]
                    );
                }

                continue;
            }

            if ($classMetadata->hasAssociation($propertyName)
                && $classMetadata->getAssociationMapping($propertyName)['type'] & ClassMetadataInfo::TO_ONE) {
                if ($importedValue !== $databaseValue) {
                    $this->fieldHelper->setObjectValue($databaseEntity, $propertyName, $importedValue);
                    $this->logger->debug(
                        'Property changed during import.',
                        [
                            'databaseEntityClass' => $databaseEntityClass,
                            'propertyName' => $propertyName,
                            'databaseValue' => serialize($this->getIdentifierValues($databaseValue)),
                            'importedValue' => serialize($this->getIdentifierValues($importedValue)),
                        ]
                    );
                }

                continue;
            }

            if ($importedValue instanceof \DateTimeInterface || $databaseValue instanceof \DateTimeInterface) {
                if ($importedValue != $databaseValue) {
                    $this->fieldHelper->setObjectValue($databaseEntity, $propertyName, $importedValue);
                    $this->logger->debug(
                        'Property changed during import.',
                        [
                            'databaseEntityClass' => $databaseEntityClass,
                            'propertyName' => $propertyName,
                            'databaseValue' => serialize($databaseValue),
                            'importedValue' => serialize($importedValue),
                        ]
                    );
                }

                continue;
            }

            if ((null !== $importedValue && null !== $databaseValue)
                && $importedValue !== $databaseValue
                && $importedValue == $databaseValue
                && gettype($importedValue) !== gettype($databaseValue)
            ) {
                $this->logger->debug(
                    'Property not changed during import, but type does not match.',
                    [
                        'databaseEntityClass' => $databaseEntityClass,
                        'propertyName' => $propertyName,
                        'databaseValue' => serialize($databaseValue),
                        'importedValue' => serialize($importedValue),
                    ]
                );

                continue;
            }

            if ($importedValue !== $databaseValue) {
                $this->fieldHelper->setObjectValue($databaseEntity, $propertyName, $importedValue);
                $this->logger->debug(
                    'Property changed during import.',
                    [
                        'databaseEntityClass' => $databaseEntityClass,
                        'propertyName' => $propertyName,
                        'databaseValue' => serialize($databaseValue),
                        'importedValue' => serialize($importedValue),
                    ]
                );
            }
        }

        if ($databaseEntity instanceof DenormalizedPropertyAwareInterface) {
            $databaseEntity->updateDenormalizedProperties();
        }
    }

    private function getIdentifierValues($object)
    {
        if (!$object) {
            return null;
        }

        $classMetadata = $this
            ->getEntityManager(ClassUtils::getClass($object))
            ->getClassMetadata(ClassUtils::getClass($object));

        return $classMetadata->getIdentifierValues($object);
    }

    public function validateEntity($entity, $constraints = null, $groups = null)
    {
        $violations = $this->validator->validate($entity, $constraints, $groups);

        $event = new StrategyValidationEvent($violations);
        $this->eventDispatcher->dispatch($event, StrategyValidationEvent::BUILD_ERRORS);

        return $event->getErrors() ?: null;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }
}
