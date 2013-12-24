<?php

namespace Oro\Bundle\EntityConfigBundle\Form\EventListener;

use Oro\Bundle\TranslationBundle\Translation\OrmTranslationMetadataCache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\TranslationBundle\Translation\Translator;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
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
     * @var OrmTranslationMetadataCache
     */
    protected $dbTranslationMetadataCache;

    /**
     * @param ConfigManager               $configManager
     * @param Translator                  $translator
     * @param OrmTranslationMetadataCache $dbTranslationMetadataCache
     */
    public function __construct(
        ConfigManager $configManager,
        Translator $translator,
        OrmTranslationMetadataCache $dbTranslationMetadataCache
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
     * if NO translations in DB -> retrieve translation from messages
     * if NO, return:
     *  field name (in case of FieldConfigModel)
     *  translation key (in case of EntityConfigModel)
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form        = $event->getForm();
        $data        = $event->getData();
        $options     = $form->getConfig()->getOptions();
        $configModel = $options['config_model'];
        $dataChanges = false;

        foreach ($this->configManager->getProviders() as $provider) {
            if (isset($data[$provider->getScope()])) {

                $type = PropertyConfigContainer::TYPE_FIELD;
                if ($configModel instanceof EntityConfigModel) {
                    $type = PropertyConfigContainer::TYPE_ENTITY;
                }
                $translatable = $provider->getPropertyConfig()->getTranslatableValues($type);

                foreach ($data[$provider->getScope()] as $code => $value) {
                    $messages = $this->translator->getTranslations()['messages'];
                    if (in_array($code, $translatable)) {
                        if (isset($messages[$value])) {
                            $value                              = $messages[$value];
                            $data[$provider->getScope()][$code] = $value;
                            $dataChanges                        = true;
                        } elseif (!$configModel->getId() && $configModel instanceof FieldConfigModel) {
                            $data[$provider->getScope()][$code] = $configModel->getFieldName();
                            $dataChanges                        = true;
                        }
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
        $options     = $event->getForm()->getConfig()->getOptions();
        $configModel = $options['config_model'];
        $data        = $event->getData();

        if ($configModel instanceof FieldConfigModel) {
            $className = $configModel->getEntity()->getClassName();
            $fieldName = $configModel->getFieldName();
        } else {
            $fieldName = null;
            $className = $configModel->getClassName();
        }

        foreach ($this->configManager->getProviders() as $provider) {
            if (isset($data[$provider->getScope()])) {

                $config = $provider->getConfig($className, $fieldName);

                /**
                 * config translations
                 */
                $type = PropertyConfigContainer::TYPE_FIELD;
                if ($configModel instanceof EntityConfigModel) {
                    $type = PropertyConfigContainer::TYPE_ENTITY;
                }
                $translatable = $provider->getPropertyConfig()->getTranslatableValues($type);

                foreach ($data[$provider->getScope()] as $code => $value) {
                    if (in_array($code, $translatable)) {
                        $key = $this->configManager->getProvider('entity')
                            ->getConfig($className, $fieldName)
                            ->get($code);

                        if ($value != $this->translator->getTranslations()['messages'][$config->get($code)]) {
                            /**
                             * save into translation table
                             */

                            /** @var TranslationRepository $translationRepo */
                            $translationRepo = $this->em->getRepository(Translation::ENTITY_NAME);

                            /** @var Translation $translationValue */
                            $translationValue = $translationRepo->findValue($key, $this->translator->getLocale());
                            if (!$translationValue) {
                                /** @var Translation $translationValue */
                                $translationValue = new Translation();
                                $translationValue
                                    ->setKey($key)
                                    ->setLocale($this->translator->getLocale())
                                    ->setValue($value)
                                    ->setDomain('messages');
                            } else {
                                $translationValue->setValue($value);
                            }

                            $this->em->persist($translationValue);

                            /**
                             * mark translation cache dirty
                             */
                            $this->dbTranslationMetadataCache->updateTimestamp($this->translator->getLocale());
                        }

                        if (!$configModel->getId()) {
                            $data[$provider->getScope()][$code] = $key;
                        } else {
                            unset($data[$provider->getScope()][$code]);
                        }
                    }
                }

                $config->setValues($data[$provider->getScope()]);
                $this->configManager->persist($config);
            }
        }

        if ($event->getForm()->isValid()) {
            $this->configManager->flush();
        }
    }
}
