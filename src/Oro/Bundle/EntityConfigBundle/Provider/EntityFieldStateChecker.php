<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Checks if state should be changed to ExtendScope::STATE_UPDATE for newly persisted FieldConfigModel.
 */
class EntityFieldStateChecker
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @param ConfigManager $configManager
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(ConfigManager $configManager, FormFactoryInterface $formFactory)
    {
        $this->configManager = $configManager;
        $this->formFactory = $formFactory;
    }

    /**
     * Checks if there are some entity config options with require_schema_update: true
     * for which value has been changed.
     *
     * @param FieldConfigModel $fieldConfigModel
     * @return bool
     */
    public function isSchemaUpdateNeeded(FieldConfigModel $fieldConfigModel)
    {
        foreach ($this->configManager->getProviders() as $provider) {
            if ($this->isUpdateRequiredForProvider($provider, $fieldConfigModel)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ConfigProvider $provider
     * @param FieldConfigModel $fieldConfigModel
     * @return bool
     */
    private function isUpdateRequiredForProvider(ConfigProvider $provider, FieldConfigModel $fieldConfigModel): bool
    {
        $configId = $this->configManager->getConfigIdByModel($fieldConfigModel, $provider->getScope());
        $propertyConfig = $provider->getPropertyConfig();

        $newFieldConfig = $this->configManager->createFieldConfigByModel($fieldConfigModel, $provider->getScope());
        $oldFieldConfig = $this->configManager->getConfig($configId);

        $newData = $newFieldConfig->getValues();
        $oldData = $oldFieldConfig->getValues();

        foreach ($newData as $code => $newValue) {
            $oldValue = $oldData[$code] ?? null;
            $schemaUpdateRequiredCallback = $this->getSchemaUpdateRequiredCallback($code, $configId, $propertyConfig);

            if ($schemaUpdateRequiredCallback && $schemaUpdateRequiredCallback($newValue, $oldValue)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $code
     * @param ConfigIdInterface $configId
     * @param PropertyConfigContainer $propertyConfig
     * @return callable|null
     */
    private function getSchemaUpdateRequiredCallback(
        string $code,
        ConfigIdInterface $configId,
        PropertyConfigContainer $propertyConfig
    ) {
        if (!$propertyConfig->isSchemaUpdateRequired($code, PropertyConfigContainer::TYPE_FIELD)) {
            return null;
        }

        $formItems = $propertyConfig->getFormItems(PropertyConfigContainer::TYPE_FIELD);
        if (!isset($formItems[$code])) {
            return null;
        }

        $formOptions = isset($formItems[$code]['form']['options']) ? $formItems[$code]['form']['options'] : [];
        $form = $this->formFactory->create($formItems[$code]['form']['type'], null, array_merge($formOptions, [
            'config_id' => $configId
        ]));

        return $form->getConfig()->getOption('schema_update_required');
    }
}
