<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;

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
        $translations = [];

        foreach ($this->configManager->getProviders() as $provider) {
            $scope = $provider->getScope();
            $data = $configModel->toArray($scope);

            if (!$data) {
                continue;
            }

            $configId = $this->configManager->getConfigIdByModel($configModel, $scope);
            $config = $provider->getConfigById($configId);
            $translatable = $provider->getPropertyConfig()->getTranslatableValues($configId);

            foreach ($data as $code => $value) {
                if (in_array($code, $translatable, true)) {
                    // check if a label text was changed
                    $labelKey = $config->get($code);

                    if (!$configModel->getId() || !$this->translationHelper->isTranslationEqual($labelKey, $value)) {
                        $translations[$labelKey] = $value;
                    }
                    // replace label text with label name in $value variable
                    $value = $labelKey;
                }
                $config->set($code, $value);
            }
            $this->configManager->persist($config);
        }

        $this->configManager->flush();
        $this->translationHelper->saveTranslations($translations);
    }
}
