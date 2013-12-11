<?php

namespace Oro\Bundle\EntityConfigBundle\Form\EventListener;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Symfony\Component\Translation\Translator;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

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
     * @var string
     */
    protected $translatorCacheDir;

    /**
     * @param ConfigManager $configManager
     * @param Translator $translator
     * @param $translatorCacheDir
     */
    public function __construct(ConfigManager $configManager, Translator $translator, $translatorCacheDir)
    {
        $this->configManager      = $configManager;
        $this->translator         = $translator;
        $this->translatorCacheDir = $translatorCacheDir;
        $this->em                 = $configManager->getEntityManager();
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
     * if NO -> return key (placeholder)
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

        if ($configModel instanceof FieldConfigModel) {
            foreach ($this->configManager->getProviders() as $provider) {
                if (isset($data[$provider->getScope()])) {
                    $translatable = $provider->getPropertyConfig()->getTranslatableValues();
                    foreach ($data[$provider->getScope()] as $code => $value) {
                        $messages = $this->translator->getTranslations()['messages'];
                        if (in_array($code, $translatable) && isset($messages[$value])) {
                            $value = $messages[$value];
                            $data[$provider->getScope()][$code] = $value;

                            $dataChanges = true;
                        }
                    }
                }
            }

            if ($dataChanges) {
                $event->setData($data);
            }
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
                $translatable = $provider->getPropertyConfig()->getTranslatableValues();
                $config = $provider->getConfig($className, $fieldName);

                foreach ($data[$provider->getScope()] as $code => $value) {
                    if (in_array($code, $translatable)) {
                        if ($value != $this->translator->getTranslations()['messages'][$config->get($code)]) {
                            /**
                             * save into translation table
                             */
                            $key = $this->configManager->getProvider('entity')
                                ->getConfig($className, $fieldName)
                                ->get($code);

                            /** @var TranslationRepository $translationRepo */
                            $translationRepo  = $this->em->getRepository(Translation::ENTITY_NAME);

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
                             * empty translations cache
                             */
                            array_map(
                                'unlink',
                                glob($this->translatorCacheDir . 'catalogue.' . $this->translator->getLocale() . '.*')
                            );
                        }
                        unset($data[$provider->getScope()][$code]);
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
