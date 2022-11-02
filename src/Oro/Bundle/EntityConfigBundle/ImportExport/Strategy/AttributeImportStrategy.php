<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Strategy;

use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * Sets the is_attribute parameter during import to differ the attribute with a non-attribute FieldConfigModel
 * during processEntity method and validation.
 */
class AttributeImportStrategy extends EntityFieldImportStrategy
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    public function setConfigHelper(ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /**
     * @param FieldConfigModel $entity
     * @return null|FieldConfigModel
     */
    protected function processEntity(FieldConfigModel $entity)
    {
        $this->configHelper->addToFieldConfigModel($entity, ['attribute' => ['is_attribute' => true]]);

        return parent::processEntity($entity);
    }

    protected function getValidationGroups(): array
    {
        return array_merge(parent::getValidationGroups(), ['AttributeField']);
    }
}
