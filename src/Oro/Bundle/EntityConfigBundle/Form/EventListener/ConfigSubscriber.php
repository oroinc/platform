<?php

namespace Oro\Bundle\EntityConfigBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;

class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var OroEntityManager
     */
    protected $em;

    /**
     * @var DynamicTranslationMetadataCache
     */
    protected $dbTranslationMetadataCache;

    /**
     * @param ConfigManager                   $configManager
     * @param Translator                      $translator
     * @param DynamicTranslationMetadataCache $dbTranslationMetadataCache
     */
    public function __construct(
        ConfigManager $configManager,
        Translator $translator,
        DynamicTranslationMetadataCache $dbTranslationMetadataCache
    ) {
        $this->configManager              = $configManager;
        $this->translator                 = $translator;
        $this->dbTranslationMetadataCache = $dbTranslationMetadataCache;
        $this->em                         = $configManager->getEntityManager();
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
     * Check for translatable values and preSet it on form
     * if have NO translation in translation catalogue return:
     *  - field name (in case of creating new FieldConfigModel)
     *  - empty string (in case of editing FieldConfigModel)
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $configModel = $event->getForm()->getConfig()->getOption('config_model');
        $data        = $event->getData();

        $dataChanges = false;
        foreach ($this->configManager->getProviders() as $provider) {
            $scope = $provider->getScope();
            if (isset($data[$scope])) {
                $configId = $this->configManager->getConfigIdByModel($configModel, $scope);

                $translatable = $provider->getPropertyConfig()->getTranslatableValues($configId);
                foreach ($data[$scope] as $code => $value) {
                    if (in_array($code, $translatable)) {
                        if ($this->translator->hasTrans($value)) {
                            $data[$scope][$code] = $this->translator->trans($value);
                        } elseif (!$configModel->getId() && $configModel instanceof FieldConfigModel) {
                            $data[$scope][$code] = $configModel->getFieldName();
                        } else {
                            $data[$scope][$code] = '';
                        }
                        $dataChanges = true;
                    }
                }
            }
        }

        if ($dataChanges) {
            $event->setData($data);
        }
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $form        = $event->getForm();
        $configModel = $form->getConfig()->getOption('config_model');
        $data        = $event->getData();

        $labelsToBeUpdated = [];
        foreach ($this->configManager->getProviders() as $provider) {
            $scope = $provider->getScope();
            if (isset($data[$scope])) {
                $configId = $this->configManager->getConfigIdByModel($configModel, $scope);
                $config   = $provider->getConfigById($configId);

                $translatable = $provider->getPropertyConfig()->getTranslatableValues($configId);
                foreach ($data[$scope] as $code => $value) {
                    if (in_array($code, $translatable)) {
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

        if ($form->isValid()) {
            // update changed labels if any
            if (!empty($labelsToBeUpdated)) {
                $locale = $this->translator->getLocale();
                foreach ($labelsToBeUpdated as $labelKey => $labelText) {
                    // save into translation table
                    /** @var TranslationRepository $translationRepo */
                    $translationRepo = $this->em->getRepository(Translation::ENTITY_NAME);
                    $translationRepo->saveValue(
                        $labelKey,
                        $labelText,
                        $locale,
                        TranslationRepository::DEFAULT_DOMAIN,
                        Translation::SCOPE_UI
                    );
                }
                // mark translation cache dirty
                $this->dbTranslationMetadataCache->updateTimestamp($locale);
            }

            $this->configManager->flush();
        }
    }
}
