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
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 *
 * Helper methods for import strategies with performance improvements:
 * - Do not override collections fully to make UoW faster
 * - Compare DateTimes by values
 * - Handle different scalar types, most of the data are strings in imported data but different types in the DB.
 */
class ConfigurableImportStrategyHelper extends ImportStrategyHelper
{
    use LoggerAwareTrait;

    private ?EventDispatcherInterface $eventDispatcher = null;

    #[\Override]
    public function importEntity($databaseEntity, $importedEntity, array $excludedProperties = []): void
    {
        parent::importEntity($databaseEntity, $importedEntity, $excludedProperties);

        if ($databaseEntity instanceof DenormalizedPropertyAwareInterface) {
            $databaseEntity->updateDenormalizedProperties();
        }
    }

    #[\Override]
    protected function processImportedEntityProperty(
        object $targetEntity,
        object $sourceEntity,
        string $property
    ): void {
        $className = ClassUtils::getClass($targetEntity);
        $metadata = $this->getEntityManager($className)->getClassMetadata($className);
        $targetValue = $this->fieldHelper->getObjectValue($targetEntity, $property);
        $sourceValue = $this->fieldHelper->getObjectValue($sourceEntity, $property);

        if ($metadata->hasAssociation($property)) {
            if ($metadata->getAssociationMapping($property)['type'] & ClassMetadataInfo::TO_MANY) {
                $this->processToManyProperty($targetEntity, $targetValue, $sourceEntity, $sourceValue, $property);
            }

            if ($metadata->getAssociationMapping($property)['type'] & ClassMetadataInfo::TO_ONE) {
                $this->processToOneProperty($targetEntity, $targetValue, $sourceValue, $property);
            }

            return;
        }

        if ($sourceValue instanceof \DateTimeInterface || $targetValue instanceof \DateTimeInterface) {
            $this->processDateTimeProperty($targetEntity, $targetValue, $sourceValue, $property);

            return;
        }

        if (
            (null !== $sourceValue && null !== $targetValue)
            && $sourceValue !== $targetValue
            && $sourceValue == $targetValue
            && gettype($sourceValue) !== gettype($targetValue)
        ) {
            $this->processPropertyWithDifferentTypes($targetEntity, $targetValue, $sourceValue, $property);

            return;
        }

        if ($sourceValue !== $targetValue) {
            $this->processPropertyWithDifferentValues($targetEntity, $targetValue, $sourceValue, $property);
        }
    }

    private function processToManyProperty(
        object $targetEntity,
        mixed $targetValue,
        object $sourceEntity,
        mixed $sourceValue,
        string $property
    ): void {
        if (!$sourceValue instanceof Collection) {
            $sourceValue = $this->fieldHelper->getObjectValueWithReflection($sourceEntity, $property);
        }

        $this->fieldHelper->setObjectValue($targetEntity, $property, $sourceValue->toArray());

        if ($targetValue instanceof PersistentCollection && $targetValue->isDirty()) {
            $this->log($targetEntity, null, null, $property, 'Property changed during import.');
        } elseif ($targetValue instanceof PersistentCollection && $targetValue->isInitialized()) {
            $this->log($targetEntity, null, null, $property, 'Property initialized but not changed during import.');
        }
    }

    private function processToOneProperty(
        object $targetEntity,
        mixed $targetValue,
        mixed $sourceValue,
        string $property
    ): void {
        if ($sourceValue === $targetValue) {
            return;
        }

        $this->fieldHelper->setObjectValue($targetEntity, $property, $sourceValue);
        $targetIdentifiers = $this->getIdentifierValues($targetValue);
        $sourceIdentifiers = $this->getIdentifierValues($sourceValue);
        $message = 'Property changed during import.';
        $this->log($targetEntity, $targetIdentifiers, $sourceIdentifiers, $property, $message);
    }

    private function processDateTimeProperty(
        object $targetEntity,
        mixed $targetValue,
        mixed $sourceValue,
        string $property
    ): void {
        if ($sourceValue == $targetValue) {
            return;
        }

        $this->fieldHelper->setObjectValue($targetEntity, $property, $sourceValue);
        $message = 'Property changed during import.';
        $this->log($targetEntity, $targetValue, $sourceValue, $property, $message);
    }

    private function processPropertyWithDifferentTypes(
        object $targetEntity,
        mixed $targetValue,
        mixed $sourceValue,
        string $property
    ): void {
        $message = 'Property not changed during import, but type does not match.';
        $this->log($targetEntity, $targetValue, $sourceValue, $property, $message);
    }

    private function processPropertyWithDifferentValues(
        object $targetEntity,
        mixed $targetValue,
        mixed $sourceValue,
        string $property
    ): void {
        $this->fieldHelper->setObjectValue($targetEntity, $property, $sourceValue);
        $message = 'Property changed during import.';
        $this->log($targetEntity, $targetValue, $sourceValue, $property, $message);
    }

    private function log(
        object $targetEntity,
        mixed $targetValue,
        mixed $sourceValue,
        string $property,
        string $message
    ): void {
        $className = ClassUtils::getClass($targetEntity);
        $context = ['databaseEntityClass' => $className, 'propertyName' => $property];
        if ($targetValue || $sourceValue) {
            $context += ['databaseValue' => serialize($targetValue), 'importedValue' => serialize($sourceValue)];
        }

        $this->logger->debug($message, $context);
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

    #[\Override]
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
