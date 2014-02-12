<?php

namespace Oro\Bundle\EntityConfigBundle\Form\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\TranslationBundle\Translation\Translator;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Entity\AbstractConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;

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
            FormEvents::POST_SUBMIT  => 'postSubmit',
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
                $translatable = $provider->getPropertyConfig()->getTranslatableValues(
                    $this->configManager->getConfigIdByModel($configModel, $scope)
                );
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
        $configModel = $event->getForm()->getConfig()->getOption('config_model');
        if ($configModel instanceof FieldConfigModel) {
            $className = $configModel->getEntity()->getClassName();
            $fieldName = $configModel->getFieldName();
        } else {
            $fieldName = null;
            $className = $configModel->getClassName();
        }

        $data = $event->getData();
        foreach ($this->configManager->getProviders() as $provider) {
            $scope = $provider->getScope();
            if (isset($data[$scope])) {
                $config = $provider->getConfig($className, $fieldName);

                // config translations
                $translatable = $provider->getPropertyConfig()->getTranslatableValues(
                    $this->configManager->getConfigIdByModel($configModel, $scope)
                );
                foreach ($data[$scope] as $code => $value) {
                    if (in_array($code, $translatable)) {
                        $key = $this->configManager->getProvider('entity')
                            ->getConfig($className, $fieldName)
                            ->get($code);

                        if ($event->getForm()->get($scope)->get($code)->isValid()
                            && $value != $this->translator->trans($config->get($code))
                        ) {
                            $locale = $this->translator->getLocale();
                            // save into translation table
                            $this->saveTranslationValue($key, $value, $locale);
                            // mark translation cache dirty
                            $this->dbTranslationMetadataCache->updateTimestamp($locale);
                        }

                        if (!$configModel->getId()) {
                            $data[$scope][$code] = $key;
                        } else {
                            unset($data[$scope][$code]);
                        }
                    }
                }

                $config->setValues($data[$scope]);
                $this->configManager->persist($config);
            }
        }

        if ($event->getForm()->isValid()) {
            $this->configManager->flush();
        }
    }

    /**
     * Update existing translation value or create new one if it is not exist
     *
     * @param string $key
     * @param string $value
     * @param string $locale
     */
    protected function saveTranslationValue($key, $value, $locale)
    {
        /** @var TranslationRepository $translationRepo */
        $translationRepo = $this->em->getRepository(Translation::ENTITY_NAME);
        /** @var Translation $translationValue */
        $translationValue = $translationRepo->findValue(
            $key,
            $locale,
            TranslationRepository::DEFAULT_DOMAIN,
            Translation::SCOPE_UI
        );
        if (!$translationValue) {
            $translationValue = new Translation();
            $translationValue
                ->setKey($key)
                ->setValue($value)
                ->setLocale($locale)
                ->setDomain(TranslationRepository::DEFAULT_DOMAIN)
                ->setScope(Translation::SCOPE_UI);
        } else {
            $translationValue->setValue($value);
        }
        $this->em->persist($translationValue);
    }
}
