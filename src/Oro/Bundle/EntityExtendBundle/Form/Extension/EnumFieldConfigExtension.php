<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumFieldConfigExtension extends AbstractTypeExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var EnumSynchronizer */
    protected $enumSynchronizer;

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
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

        $enumName = $data['enum']['enum_name'];
        $enumCode = null;
        if (isset($enumConfig['enum_code'])) {
            $enumCode = $enumConfig['enum_code'];
        }
        if (empty($enumCode)) {
            $enumCode = ExtendHelper::buildEnumCode($enumName);
        }

        $locale             = $this->translator->getLocale();
        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
        $enumConfigProvider = $this->configManager->getProvider('enum');
        if ($enumConfigProvider->hasConfig($enumValueClassName)) {
            $this->enumSynchronizer->applyEnumNameTrans($enumCode, $enumName, $locale);
            $this->enumSynchronizer->applyEnumOptions($enumValueClassName, $data['enum']['enum_options'], $locale);
            $this->enumSynchronizer->applyEnumEntityOptions($enumValueClassName, $data['enum']['enum_public']);

            unset($data['enum']['enum_name']);
            unset($data['enum']['enum_public']);
            unset($data['enum']['enum_options']);
            $event->setData($data);
        } else {
            $data['enum']['enum_locale'] = $locale;
            $event->setData($data);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'oro_entity_config_type';
    }
}
