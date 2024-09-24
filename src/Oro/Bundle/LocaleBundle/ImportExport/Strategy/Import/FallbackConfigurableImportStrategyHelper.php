<?php

namespace Oro\Bundle\LocaleBundle\ImportExport\Strategy\Import;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableImportStrategyHelper;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

/**
 * Responsible for importing fields with localization.
 * Unlike other collections, you cannot delete values in collections with translations,
 * but only change their fallbacks or add new.
 */
class FallbackConfigurableImportStrategyHelper extends ConfigurableImportStrategyHelper
{
    #[\Override]
    protected function processImportedEntityProperty(object $targetEntity, object $sourceEntity, string $property): void
    {
        $className = ClassUtils::getClass($targetEntity);
        $metadata = $this->getEntityManager($className)->getClassMetadata($className);

        if ($this->propertyIsLocalizedFallbackValue($metadata, $property)) {
            $targetValue = $this->fieldHelper->getObjectValue($targetEntity, $property);
            $sourceValue = $this->fieldHelper->getObjectValue($sourceEntity, $property);

            // Some localized collection values update on ConfigurableAddOrReplaceStrategy::updateRelations,
            // so don't need to add them to the collection again.
            foreach ($sourceValue as $value) {
                if (!$targetValue->contains($value)) {
                    $targetValue->add($value);
                }
            }

            if ($targetValue instanceof PersistentCollection && $targetValue->isDirty()) {
                $this->log($targetEntity, $property, 'Property changed during import.');
            } elseif ($targetValue instanceof PersistentCollection && $targetValue->isInitialized()) {
                $this->log($targetEntity, $property, 'Property initialized but not changed during import.');
            }

            return;
        }

        parent::processImportedEntityProperty($targetEntity, $sourceEntity, $property);
    }

    private function log(object $targetEntity, string $property, string $message): void
    {
        $className = ClassUtils::getClass($targetEntity);
        $this->logger->debug($message, ['databaseEntityClass' => $className, 'propertyName' => $property]);
    }

    /**
     * The TO_MANY relationship returns a collection.
     */
    private function propertyIsLocalizedFallbackValue(ClassMetadata $metadata, string $property): bool
    {
        if (!$metadata->hasAssociation($property)) {
            return false;
        }

        $association = $metadata->getAssociationMapping($property);

        return
            $association['type'] & ClassMetadataInfo::TO_MANY
            && is_a($association['targetEntity'], AbstractLocalizedFallbackValue::class, true);
    }
}
