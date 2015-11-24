<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EntityFieldWriter implements ItemWriterInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ConfigTranslationHelper */
    protected $translationHelper;

    /** @var EnumSynchronizer */
    protected $enumSynchronizer;

    /**
     * @param ConfigManager $configManager
     * @param ConfigTranslationHelper $translationHelper
     * @param EnumSynchronizer $enumSynchronizer
     */
    public function __construct(
        ConfigManager $configManager,
        ConfigTranslationHelper $translationHelper,
        EnumSynchronizer $enumSynchronizer
    ) {
        $this->configManager = $configManager;
        $this->translationHelper = $translationHelper;
        $this->enumSynchronizer = $enumSynchronizer;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $translations = [];

        foreach ($items as $item) {
            $translations = array_merge($translations, $this->writeItem($item));
        }

        $this->configManager->flush();
        $this->translationHelper->saveTranslations($translations);
    }

    /**
     * @param FieldConfigModel $configModel
     * @return array
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

            $translations = array_merge(
                $translations,
                $this->processData($provider, $provider->getConfig($className, $fieldName), $data, $state)
            );
        }

        $this->setExtendData($className, $fieldName, $state);
        $this->updateEntityState($className);

        if ($state === ExtendScope::STATE_UPDATE && in_array($configModel->getType(), ['enum', 'multiEnum'], true)) {
            $this->setEnumData($configModel->toArray('enum'), $className, $fieldName);
        }

        return $translations;
    }

    /**
     * @param ConfigProvider $provider
     * @param ConfigInterface $config
     * @param array $data
     * @param string $state
     * @return array
     */
    protected function processData(ConfigProvider $provider, ConfigInterface $config, array $data, $state)
    {
        if ($provider->getScope() === 'enum' && $config->get('enum_code')) {
            return [];
        }

        $translatable = $provider->getPropertyConfig()->getTranslatableValues($config->getId());
        $translations = [];

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

        return $translations;
    }

    /**
     * @param string $className
     */
    protected function updateEntityState($className)
    {
        $provider = $this->configManager->getProvider('extend');
        if (!$provider) {
            return;
        }

        $entityConfig = $provider->getConfig($className);
        if (!$entityConfig->is('state', ExtendScope::STATE_UPDATE)) {
            $entityConfig->set('state', ExtendScope::STATE_UPDATE);
            $this->configManager->persist($entityConfig);
        }
    }

    /**
     * @param array $data
     * @param string $className
     * @param string $fieldName
     */
    protected function setEnumData(array $data, $className, $fieldName)
    {
        $provider = $this->configManager->getProvider('enum');
        if (!$provider) {
            return;
        }

        $enumCode = $provider->getConfig($className, $fieldName)->get('enum_code');

        if (!$enumCode || !isset($data['enum_options'])) {
            return;
        }

        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);

        $enumOptions = array_map(
            function ($option) {
                if (!isset($option['id'])) {
                    $option['id'] = $option['label'];
                }

                return $option;
            },
            $data['enum_options']
        );

        if ($provider->hasConfig($enumValueClassName)) {
            $this->enumSynchronizer->applyEnumOptions(
                $enumValueClassName,
                $enumOptions,
                $this->translationHelper->getLocale()
            );
        }
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $state
     */
    protected function setExtendData($className, $fieldName, $state)
    {
        $provider = $this->configManager->getProvider('extend');
        if (!$provider) {
            return;
        }

        $config = $provider->getConfig($className, $fieldName);
        $data = [
            'owner' => ExtendScope::OWNER_CUSTOM,
            'state' =>  $config->is('state', ExtendScope::STATE_NEW) ? ExtendScope::STATE_NEW : $state,
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
