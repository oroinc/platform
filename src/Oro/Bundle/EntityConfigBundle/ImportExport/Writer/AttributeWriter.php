<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Writer;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\SerializedFieldProvider;

class AttributeWriter extends EntityFieldWriter
{
    /**
     * @var SerializedFieldProvider
     */
    protected $serializedFieldProvider;

    /**
     * @param SerializedFieldProvider $serializedFieldProvider
     */
    public function setSerializedFieldProvider(SerializedFieldProvider $serializedFieldProvider)
    {
        $this->serializedFieldProvider = $serializedFieldProvider;
    }

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @param string $state
     */
    protected function setExtendData(FieldConfigModel $fieldConfigModel, $state)
    {
        parent::setExtendData($fieldConfigModel, $state);

        $this->setAttributeData($fieldConfigModel);
    }

    /**
     * @param FieldConfigModel $fieldConfigModel
     */
    protected function setAttributeData(FieldConfigModel $fieldConfigModel)
    {
        $attributeProvider = $this->configManager->getProvider('attribute');
        $extendProvider = $this->configManager->getProvider('extend');
        if (!$attributeProvider || !$extendProvider) {
            return;
        }
        $className = $fieldConfigModel->getEntity()->getClassName();
        $fieldName = $fieldConfigModel->getFieldName();

        $attributeConfig = $attributeProvider->getConfig($className, $fieldName);
        $attributeConfig->set('is_attribute', true);
        $this->configManager->persist($attributeConfig);

        $isSerialized = $this->serializedFieldProvider->isSerialized($fieldConfigModel);
        $extendConfig = $extendProvider->getConfig($className, $fieldName);
        $extendConfig->set('is_serialized', $isSerialized);
        $this->configManager->persist($extendConfig);
    }
}
