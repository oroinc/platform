<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Writer;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\SerializedFieldProvider;

/**
 * Writer adds is_serialized config value before writing.
 */
class AttributeWriter extends EntityFieldWriter
{
    /**
     * @var SerializedFieldProvider
     */
    protected $serializedFieldProvider;

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

    protected function setAttributeData(FieldConfigModel $fieldConfigModel)
    {
        $extendProvider = $this->configManager->getProvider('extend');
        if (!$extendProvider) {
            return;
        }
        $className = $fieldConfigModel->getEntity()->getClassName();
        $fieldName = $fieldConfigModel->getFieldName();

        $isSerialized = $this->serializedFieldProvider->isSerialized($fieldConfigModel);
        $extendConfig = $extendProvider->getConfig($className, $fieldName);
        $extendConfig->set('is_serialized', $isSerialized);
        $this->configManager->persist($extendConfig);
    }
}
