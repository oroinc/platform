<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener\Metadata;

use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

/**
 * The listener to build entity merge metadata.
 */
class EntityConfigListener
{
    const CONFIG_MERGE_SCOPE = 'merge';
    const INVERSE_OPTION_PREFIX = 'inverse_';

    /**
     * @var EntityConfigHelper
     */
    protected $entityConfigHelper;

    public function __construct(EntityConfigHelper $entityConfigHelper)
    {
        $this->entityConfigHelper = $entityConfigHelper;
    }

    public function onCreateMetadata(EntityMetadataEvent $event)
    {
        $entityMetadata = $event->getEntityMetadata();

        $this->applyEntityMetadataConfig($entityMetadata);

        foreach ($entityMetadata->getFieldsMetadata() as $fieldMetadata) {
            $this->applyFieldMetadataConfig($fieldMetadata);
        }
    }

    protected function applyEntityMetadataConfig(EntityMetadata $entityMetadata)
    {
        $className = $entityMetadata->getClassName();

        $mergeConfig = $this->entityConfigHelper->getConfig(self::CONFIG_MERGE_SCOPE, $className, null);

        if ($mergeConfig) {
            $entityMetadata->merge($mergeConfig->all());
        }
    }

    protected function applyFieldMetadataConfig(FieldMetadata $fieldMetadata)
    {
        $this->entityConfigHelper->prepareFieldMetadataPropertyPath($fieldMetadata);

        $mergeConfig = $this->entityConfigHelper->getConfigByFieldMetadata(self::CONFIG_MERGE_SCOPE, $fieldMetadata);

        if ($mergeConfig) {
            $mergeOptions = $this->filterInverseOptions(
                $mergeConfig->all(),
                $fieldMetadata->isDefinedBySourceEntity()
            );

            $fieldMetadata->merge($mergeOptions);
        }
    }

    /**
     * @param array $options
     * @param bool $definedBySourceEntity
     * @return array
     */
    protected function filterInverseOptions(array $options, $definedBySourceEntity)
    {
        $result = [];
        $overrideOptions = [];

        foreach ($options as $key => $value) {
            if (str_starts_with($key, self::INVERSE_OPTION_PREFIX)) {
                $key = substr($key, \strlen(self::INVERSE_OPTION_PREFIX));
                $overrideOptions[$key] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $definedBySourceEntity ? $result : array_merge($result, $overrideOptions);
    }
}
