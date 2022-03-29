<?php

namespace Oro\Bundle\EntityConfigBundle\Form\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form listener to update configs and translatable values
 */
class ConfigSubscriber implements EventSubscriberInterface
{
    const NEW_PENDING_VALUE_KEY = 1;

    /** @var ConfigTranslationHelper */
    protected $translationHelper;

    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(
        ConfigTranslationHelper $translationHelper,
        ConfigManager $configManager,
        TranslatorInterface $translator
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
        return [
            FormEvents::POST_SUBMIT  => ['postSubmit', -10],
            FormEvents::PRE_SET_DATA => 'preSetData'
        ];
    }

    public function preSetData(FormEvent $event)
    {
        $formConfig = $event->getForm()->getConfig();

        /** @var FieldConfigModel $configModel */
        $configModel = $formConfig->getOption('config_model');

        $event->setData(
            $this->updateTranslatableValues(
                $formConfig->getOption('field_name'),
                $configModel,
                $this->updateDataWithPendingChanges($configModel, $event->getData())
            )
        );
    }

    public function postSubmit(FormEvent $event)
    {
        $form        = $event->getForm();
        $configModel = $form->getConfig()->getOption('config_model');

        $this->updateConfigs(
            $configModel,
            $this->updatePendingChanges($configModel, $event->getData()),
            $form->isValid() && !$this->isClickedButton($form, ConfigType::PARTIAL_SUBMIT)
        );
    }

    private function isClickedButton(FormInterface $form, string $buttonName): string
    {
        return method_exists($form, 'getClickedButton') && $form->getClickedButton()?->getName() === $buttonName;
    }

    /**
     * @param ConfigModel $configModel
     * @param array $data
     * @param bool $flush
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
                    if ($configModel->getId() &&
                        $configModel instanceof FieldConfigModel &&
                        isset($changeSet[$code][static::NEW_PENDING_VALUE_KEY])
                    ) {
                        // we shouldn't overwrite config's value by data from form,
                        // if it was directly changed earlier by some form's listener or something like that
                        $value = $changeSet[$code][static::NEW_PENDING_VALUE_KEY];
                    }
                    if (in_array($code, $translatable, true)) {
                        // check if a label text was changed
                        $labelKey = (string) $config->get($code);
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
     * @param string $fieldName
     * @param ConfigModel $configModel
     * @param array $data
     *
     * @return array
     */
    protected function updateTranslatableValues($fieldName, ConfigModel $configModel, array $data)
    {
        foreach ($this->configManager->getProviders() as $provider) {
            $scope = $provider->getScope();
            if (isset($data[$scope])) {
                $configId = $this->configManager->getConfigIdByModel($configModel, $scope);

                $translatable = $provider->getPropertyConfig()->getTranslatableValues($configId);
                foreach ($data[$scope] as $code => $value) {
                    if (in_array($code, $translatable, true)) {
                        $data[$scope][$code] = $this->translationHelper->translateWithFallback($value, $fieldName);
                    }
                }
            }
        }

        return $data;
    }
}
