<?php

namespace Oro\Bundle\EntityConfigBundle\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;

class ConfigSubscriber implements EventSubscriberInterface
{
    const NEW_PENDING_VALUE_KEY = 1;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var DynamicTranslationMetadataCache
     */
    protected $dbTranslationMetadataCache;

    /**
     * @param ManagerRegistry                 $doctrine
     * @param ConfigManager                   $configManager
     * @param Translator                      $translator
     * @param DynamicTranslationMetadataCache $dbTranslationMetadataCache
     */
    public function __construct(
        ManagerRegistry $doctrine,
        ConfigManager $configManager,
        Translator $translator,
        DynamicTranslationMetadataCache $dbTranslationMetadataCache
    ) {
        $this->doctrine                   = $doctrine;
        $this->configManager              = $configManager;
        $this->translator                 = $translator;
        $this->dbTranslationMetadataCache = $dbTranslationMetadataCache;
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

                $translatable = $provider->getPropertyConfig()->getTranslatableValues($configId);
                foreach ($data[$scope] as $code => $value) {
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
            if (!empty($labelsToBeUpdated)) {
                /** @var EntityManager $translationEm */
                $translationEm = $this->doctrine->getManagerForClass(Translation::ENTITY_NAME);
                /** @var TranslationRepository $translationRepo */
                $translationRepo = $translationEm->getRepository(Translation::ENTITY_NAME);

                $values = [];
                $locale = $this->translator->getLocale();
                foreach ($labelsToBeUpdated as $labelKey => $labelText) {
                    // save into translation table
                    $values[] = $translationRepo->saveValue(
                        $labelKey,
                        $labelText,
                        $locale,
                        TranslationRepository::DEFAULT_DOMAIN,
                        Translation::SCOPE_UI
                    );
                }
                // mark translation cache dirty
                $this->dbTranslationMetadataCache->updateTimestamp($locale);

                $translationEm->flush($values);
            }

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
