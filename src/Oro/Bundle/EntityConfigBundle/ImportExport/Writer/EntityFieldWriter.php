<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class EntityFieldWriter implements ItemWriterInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ConfigTranslationHelper */
    protected $translationHelper;

    /**
     * @param ConfigManager $configManager
     * @param ConfigTranslationHelper $translationHelper
     */
    public function __construct(ConfigManager $configManager, ConfigTranslationHelper $translationHelper)
    {
        $this->configManager = $configManager;
        $this->translationHelper = $translationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        foreach ($items as $item) {
            $this->writeItem($item);
        }
    }

    /**
     * @param FieldConfigModel $configModel
     */
    protected function writeItem(FieldConfigModel $configModel)
    {
        $className = $configModel->getEntity()->getClassName();
        $fieldName = $configModel->getFieldName();
        $state = ExtendScope::STATE_UPDATE;

        if (!$this->configManager->hasConfig($className, $fieldName)) {
            $this->configManager->createConfigFieldModel($className, $fieldName, $configModel->getType());
            $state = ExtendScope::STATE_NEW;
        }

        $translations = [];
        foreach ($this->configManager->getProviders() as $provider) {
            $scope = $provider->getScope();
            $data = $configModel->toArray($scope);

            if (!$data) {
                continue;
            }

            $config = $provider->getConfig($className, $fieldName);
            $translatable = $provider->getPropertyConfig()->getTranslatableValues($config->getId());

            foreach ($data as $code => $value) {
                if (in_array($code, $translatable, true)) {
                    // check if a label text was changed
                    $labelKey = $config->get($code);

                    if ($state === ExtendScope::STATE_NEW ||
                        !$this->translationHelper->isTranslationEqual($labelKey, $value)
                    ) {
                        $translations[$labelKey] = $value;
                    }
                    // replace label text with label name in $value variable
                    $value = $labelKey;
                }
                $config->set($code, $value);
            }
            $this->configManager->persist($config);
        }

        $this->setExtendData($className, $fieldName, $state);

        $entityConfig = $this->configManager->getProvider('extend')->getConfig($className);
        if (!$entityConfig->is('state', ExtendScope::STATE_UPDATE)) {
            $entityConfig->set('state', ExtendScope::STATE_UPDATE);
            $this->configManager->persist($entityConfig);
        }

        $this->configManager->flush();
        $this->translationHelper->saveTranslations($translations);
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $state
     */
    protected function setExtendData($className, $fieldName, $state)
    {
        $extendProvider = $this->configManager->getProvider('extend');
        $config = $extendProvider->getConfig($className, $fieldName);

        $data = [
            'owner' => ExtendScope::OWNER_CUSTOM,
            'state' => $state,
            'origin' => ExtendScope::ORIGIN_CUSTOM,
            'is_extend' => true,
            'is_deleted' => false,
            'is_serialized' => false
        ];

        foreach ($data as $code => $value) {
            $config->set($code, $value);
        }

        $this->configManager->persist($config);
    }
}
