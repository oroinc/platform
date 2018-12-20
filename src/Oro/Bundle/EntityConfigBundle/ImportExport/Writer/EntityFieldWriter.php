<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\EntityFieldStateChecker;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Import writer for extend entity attributes
 */
class EntityFieldWriter implements ItemWriterInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ConfigTranslationHelper */
    protected $translationHelper;

    /** @var EnumSynchronizer */
    protected $enumSynchronizer;

    /** @var EntityFieldStateChecker */
    private $stateChecker;

    /**
     * @param ConfigManager $configManager
     * @param ConfigTranslationHelper $translationHelper
     * @param EnumSynchronizer $enumSynchronizer
     * @param EntityFieldStateChecker $entityFieldStateChecker
     */
    public function __construct(
        ConfigManager $configManager,
        ConfigTranslationHelper $translationHelper,
        EnumSynchronizer $enumSynchronizer,
        EntityFieldStateChecker $entityFieldStateChecker
    ) {
        $this->configManager = $configManager;
        $this->translationHelper = $translationHelper;
        $this->enumSynchronizer = $enumSynchronizer;
        $this->stateChecker = $entityFieldStateChecker;
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
        $state = ExtendScope::STATE_ACTIVE;

        if (!$this->configManager->hasConfig($className, $fieldName)) {
            $this->configManager->createConfigFieldModel($className, $fieldName, $configModel->getType());
            $state = ExtendScope::STATE_NEW;
        }

        if ($state === ExtendScope::STATE_ACTIVE && $this->stateChecker->isSchemaUpdateNeeded($configModel)) {
            $state = ExtendScope::STATE_UPDATE;
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

        $this->setExtendData($configModel, $state);

        if ($state !== ExtendScope::STATE_NEW && in_array($configModel->getType(), ['enum', 'multiEnum'], true)) {
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

        if ($provider->hasConfig($enumValueClassName)) {
            $this->enumSynchronizer->applyEnumOptions(
                $enumValueClassName,
                $this->getUniqueOptions($data['enum_options']),
                $this->translationHelper->getLocale()
            );
        }
    }

    /**
     * @param FieldConfigModel $configModel
     * @param string $state
     */
    protected function setExtendData(FieldConfigModel $configModel, $state)
    {
        $provider = $this->configManager->getProvider('extend');
        if (!$provider) {
            return;
        }
        $className = $configModel->getEntity()->getClassName();
        $fieldName = $configModel->getFieldName();

        $config = $provider->getConfig($className, $fieldName);
        $data = [
            'owner' => ExtendScope::OWNER_CUSTOM,
            'state' =>  $config->is('state', ExtendScope::STATE_NEW) ? ExtendScope::STATE_NEW : $state,
            'origin' => ExtendScope::ORIGIN_CUSTOM,
            'is_extend' => true,
            'is_deleted' => false,
            'is_serialized' => $config->get('is_serialized', false, false)
        ];

        foreach ($data as $code => $value) {
            $config->set($code, $value);
        }

        $this->configManager->persist($config);
    }

    /**
     * @param array $options
     * @return array
     */
    private function getUniqueOptions(array $options)
    {
        $processedIds = [];
        $processedLabels = [];
        $result = [];

        foreach ($options as $key => $opts) {
            if (isset($opts['id']) && $opts['id'] !== null && $opts['id'] !== '') {
                if (empty($processedIds[$opts['id']])) {
                    $processedIds[$opts['id']] = true;
                    $result[$key] = $opts;
                }
            } elseif (isset($opts['label'])) {
                $labelKey = trim($opts['label']);
                if (empty($processedLabels[$labelKey])) {
                    $processedLabels[$labelKey] = true;
                    $result[$key] = $opts;
                }
            }
        }

        return $result;
    }
}
