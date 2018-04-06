<?php

namespace Oro\Bundle\EntityConfigBundle\Form\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ConfigSubscriber implements EventSubscriberInterface
{
    const NEW_PENDING_VALUE_KEY = 1;

    /**
     * @var ConfigTranslationHelper
     */
    protected $translationHelper;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param ConfigTranslationHelper $translationHelper
     * @param ConfigManager $configManager
     * @param Translator $translator
     */
    public function __construct(
        ConfigTranslationHelper $translationHelper,
        ConfigManager $configManager,
        Translator $translator
    ) {
        $this->translationHelper = $translationHelper;
        $this->configManager = $configManager;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::POST_SUBMIT  => ['postSubmit', -10],
            FormEvents::PRE_SET_DATA => 'preSetData'
        );
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $configModel = $event->getForm()->getConfig()->getOption('config_model');

        $event->setData(
            $this->updateTranslatableValues(
                $configModel,
                $this->updateDataWithPendingChanges($configModel, $event->getData())
            )
        );
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $form        = $event->getForm();
        $configModel = $form->getConfig()->getOption('config_model');

        $this->updateConfigs(
            $configModel,
            $this->updatePendingChanges($configModel, $event->getData()),
            $form->isValid()
        );
    }

    /**
     * @param ConfigModel $configModel
     * @param array $data
     * @param bool $flush
     */
    protected function updateConfigs(ConfigModel $configModel, array $data, $flush)
    {
        $labelsToBeUpdated = [];
        foreach ($this->configManager->getProviders() as $provider) {
            $scope = $provider->getScope();
            if (isset($data[$scope])) {
                $configId = $this->configManager->getConfigIdByModel($configModel, $scope);
                $config   = $provider->getConfigById($configId);
                $this->configManager->calculateConfigChangeSet($config);
                $changeSet = $this->configManager->getConfigChangeSet($config);

                $translatable = $provider->getPropertyConfig()->getTranslatableValues($configId);
                foreach ($data[$scope] as $code => $value) {
                    if (isset($changeSet[$code][static::NEW_PENDING_VALUE_KEY])) {
                        // we shouldn't overwrite config's value by data from form,
                        // if it was directly changed earlier by some form's listener or something like that
                        $value = $changeSet[$code][static::NEW_PENDING_VALUE_KEY];
                    }
                    if (in_array($code, $translatable, true)) {
                        // check if a label text was changed
                        $labelKey = $config->get($code);
                        if (!$configModel->getId()) {
                            $labelsToBeUpdated[$labelKey] = $value;
                        } elseif ($value != $this->translator->trans($labelKey)) {
                            $labelsToBeUpdated[$labelKey] = $value;
                        }
                        // replace label text with label name in $value variable
                        $value = $config->get($code);
                    }

                    $config->set($code, $value);
                }

                $this->configManager->persist($config);
            }
        }

        if ($flush) {
            // update changed labels if any
            $this->translationHelper->saveTranslations($labelsToBeUpdated);

            $this->configManager->flush();
        }
    }

    /**
     * @param ConfigModel $configModel
     * @param array $data
     *
     * @return array
     */
    protected function updatePendingChanges(ConfigModel $configModel, array $data)
    {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendConfigId = $this->configManager->getConfigIdByModel($configModel, 'extend');
        $extendConfig = $extendConfigProvider->getConfigById($extendConfigId);
        $pendingChanges = $extendConfig->get('pending_changes', false, []);

        if (!$pendingChanges) {
            return $data;
        }

        $scopes = array_keys($pendingChanges);
        foreach ($scopes as $scope) {
            if (!isset($data[$scope])) {
                continue;
            }

            $values = array_intersect_key($data[$scope], $pendingChanges[$scope]);
            foreach ($values as $code => $value) {
                $pendingChanges[$scope][$code][static::NEW_PENDING_VALUE_KEY] = $value;
                unset($data[$scope][$code]);
            }
        }

        $extendConfig->set('pending_changes', $pendingChanges);
        $this->configManager->persist($extendConfig);

        return $data;
    }

    /**
     * @param ConfigModel $configModel
     * @param array $data
     *
     * @return array
     */
    protected function updateDataWithPendingChanges(ConfigModel $configModel, array $data)
    {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendConfigId = $this->configManager->getConfigIdByModel($configModel, 'extend');
        $extendConfig = $extendConfigProvider->getConfigById($extendConfigId);
        $pendingChanges = $extendConfig->get('pending_changes', false, []);
        foreach ($pendingChanges as $scope => $values) {
            foreach ($values as $code => $value) {
                $currentVal = isset($data[$scope][$code]) ? $data[$scope][$code] : null;
                $data[$scope][$code] = ExtendHelper::updatedPendingValue($currentVal, $value);
            }
        }

        return $data;
    }

    /**
     * Check for translatable values and set it to data
     * if have NO translation in translation catalogue return:
     *  - field name (in case of creating new FieldConfigModel)
     *  - empty string (in case of editing FieldConfigModel)
     *
     * @param ConfigModel $configModel
     * @param array $data
     *
     * @return array
     */
    protected function updateTranslatableValues(ConfigModel $configModel, array $data)
    {
        foreach ($this->configManager->getProviders() as $provider) {
            $scope = $provider->getScope();
            if (isset($data[$scope])) {
                $configId = $this->configManager->getConfigIdByModel($configModel, $scope);

                $translatable = $provider->getPropertyConfig()->getTranslatableValues($configId);
                foreach ($data[$scope] as $code => $value) {
                    if (in_array($code, $translatable, true)) {
                        if ($this->translator->hasTrans($value)) {
                            $data[$scope][$code] = $this->translator->trans($value);
                        } elseif (!$configModel->getId() && $configModel instanceof FieldConfigModel) {
                            $data[$scope][$code] = $configModel->getFieldName();
                        } else {
                            $data[$scope][$code] = '';
                        }
                    }
                }
            }
        }

        return $data;
    }
}
