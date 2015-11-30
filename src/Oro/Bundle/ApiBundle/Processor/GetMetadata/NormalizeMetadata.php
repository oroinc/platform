<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class NormalizeMetadata implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var MetadataContext $context */

        if (!$context->hasResult()) {
            // metadata is not loaded
            return;
        }

        $config = $context->getConfig();
        if (empty($config)) {
            // a configuration does not exist
            return;
        }

        /** @var EntityMetadata $entityMetadata */
        $entityMetadata = $context->getResult();
        $this->normalizeMetadata($entityMetadata, $config);
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @param array          $config
     */
    protected function normalizeMetadata(EntityMetadata $entityMetadata, array $config)
    {
        $fields = ConfigUtil::getArrayValue($config, ConfigUtil::FIELDS);
        foreach ($fields as $fieldName => $fieldConfig) {
            if (null === $fieldConfig) {
                continue;
            }
            if (ConfigUtil::isExclude($fieldConfig)) {
                $this->removeProperty($entityMetadata, $fieldName);
            } elseif (isset($fieldConfig[ConfigUtil::PROPERTY_PATH])) {
                $path = ConfigUtil::explodePropertyPath($fieldConfig[ConfigUtil::PROPERTY_PATH]);
                if (count($path) === 1) {
                    $this->renameProperty($entityMetadata, reset($path), $fieldName);
                }
            }
        }

        if (ConfigUtil::isExcludeAll($config)) {
            $toRemoveFieldNames = array_diff(
                array_merge(array_keys($entityMetadata->getFields()), array_keys($entityMetadata->getAssociations())),
                array_keys($fields)
            );
            foreach ($toRemoveFieldNames as $fieldName) {
                $this->removeProperty($entityMetadata, $fieldName);
            }
        }
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @param string         $name
     */
    protected function removeProperty(EntityMetadata $entityMetadata, $name)
    {
        if ($entityMetadata->hasField($name)) {
            $entityMetadata->removeField($name);
        } elseif ($entityMetadata->hasAssociation($name)) {
            $entityMetadata->removeAssociation($name);
        }
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @param string         $oldName
     * @param string         $newName
     */
    protected function renameProperty(EntityMetadata $entityMetadata, $oldName, $newName)
    {
        if ($entityMetadata->hasField($oldName)) {
            $entityMetadata->renameField($oldName, $newName);
        } elseif ($entityMetadata->hasAssociation($oldName)) {
            $entityMetadata->renameAssociation($oldName, $newName);
        }
    }
}
