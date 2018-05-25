<?php

namespace Oro\Bundle\EntityExtendBundle\Form\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Manage Entity Config Enum Options
 */
class EnumFieldConfigSubscriber implements EventSubscriberInterface, LoggerAwareInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var EnumSynchronizer */
    protected $enumSynchronizer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ConfigManager       $configManager
     * @param TranslatorInterface $translator
     * @param EnumSynchronizer    $enumSynchronizer
     */
    public function __construct(
        ConfigManager $configManager,
        TranslatorInterface $translator,
        EnumSynchronizer $enumSynchronizer
    ) {
        $this->configManager    = $configManager;
        $this->translator       = $translator;
        $this->enumSynchronizer = $enumSynchronizer;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    /**
     * Pre set data event handler
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form        = $event->getForm();
        $configModel = $form->getConfig()->getOption('config_model');

        if (!($configModel instanceof FieldConfigModel)) {
            return;
        }
        if (!in_array($configModel->getType(), ['enum', 'multiEnum'])) {
            return;
        };

        $enumConfig = $configModel->toArray('enum');
        if (empty($enumConfig['enum_code'])) {
            // new enum - a form already has a all data because on submit them are not removed from a config
            return;
        }

        $enumCode = $enumConfig['enum_code'];
        $data     = $event->getData();

        $data['enum']['enum_name'] = $this->translator->trans(
            ExtendHelper::getEnumTranslationKey('label', $enumCode)
        );

        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
        $enumConfigProvider = $this->configManager->getProvider('enum');
        if ($enumConfigProvider->hasConfig($enumValueClassName)) {
            $enumEntityConfig             = $enumConfigProvider->getConfig($enumValueClassName);
            $data['enum']['enum_public']  = $enumEntityConfig->get('public');
            $data['enum']['enum_options'] = $this->enumSynchronizer->getEnumOptions($enumValueClassName);
        }

        $event->setData($data);
    }

    /**
     * Post submit event handler
     *
     * @param FormEvent $event
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function postSubmit(FormEvent $event)
    {
        $form        = $event->getForm();
        $configModel = $form->getConfig()->getOption('config_model');

        if (!($configModel instanceof FieldConfigModel)) {
            return;
        }
        if (!in_array($configModel->getType(), ['enum', 'multiEnum'])) {
            return;
        };
        if (!$form->isValid()) {
            return;
        }

        $data       = $event->getData();
        $enumConfig = $configModel->toArray('enum');

        $enumName = $this->getValue($data['enum'], 'enum_name');
        $enumCode = $this->getValue($enumConfig, 'enum_code');
        if (empty($enumCode)) {
            $enumCode = $enumName !== null
                ? ExtendHelper::buildEnumCode($enumName)
                : ExtendHelper::generateEnumCode(
                    $configModel->getEntity()->getClassName(),
                    $configModel->getFieldName()
                );
        }

        $locale             = $this->translator->getLocale();
        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
        $enumConfigProvider = $this->configManager->getProvider('enum');

        if ($enumConfigProvider->hasConfig($enumValueClassName)) {
            try {
                // existing enum
                if ($configModel->getId()) {
                    if ($enumName !== null) {
                        $this->enumSynchronizer->applyEnumNameTrans($enumCode, $enumName, $locale);
                    }
                    $enumOptions = $this->getValue($data['enum'], 'enum_options');
                    if ($enumOptions !== null) {
                        $this->enumSynchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);
                    }
                    $enumPublic = $this->getValue($data['enum'], 'enum_public');
                    if ($enumPublic !== null) {
                        $this->enumSynchronizer->applyEnumEntityOptions($enumValueClassName, $enumPublic);
                    }
                }

                unset($data['enum']['enum_name']);
                unset($data['enum']['enum_options']);
                unset($data['enum']['enum_public']);
                $event->setData($data);
            } catch (\Exception $e) {
                $form->addError(
                    new FormError(
                        $this->translator->trans('oro.entity_extend.enum.options_error.message', [], 'validators')
                    )
                );
                if (null !== $this->logger) {
                    $this->logger->error('Error occurred during enum options save', ['exception'=> $e]);
                }
            }
        } else {
            // new enum
            $this->sortOptions($data['enum']['enum_options']);
            $data['enum']['enum_locale'] = $locale;
            $event->setData($data);
        }
    }

    /**
     * @param array  $values
     * @param string $name
     * @return mixed
     */
    protected function getValue(array $values, $name)
    {
        return isset($values[$name]) && array_key_exists($name, $values)
            ? $values[$name]
            : null;
    }

    /**
     * @param array $options
     */
    protected function sortOptions(array &$options)
    {
        usort(
            $options,
            function ($a, $b) {
                if ($a['priority'] == $b['priority']) {
                    return 0;
                }

                return $a['priority'] < $b['priority'] ? -1 : 1;
            }
        );
        $index = 0;
        foreach ($options as &$option) {
            $option['priority'] = ++$index;
        }
    }
}
