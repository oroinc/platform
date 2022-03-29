<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\EventListener\ConfigSubscriber;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The form for entity and entity field configuration options.
 */
class ConfigType extends AbstractType
{
    public const PARTIAL_SUBMIT = 'partialSubmit';

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $configModel = $options['config_model'];
        $data        = [];

        if ($configModel instanceof FieldConfigModel) {
            $className  = $configModel->getEntity()->getClassName();
            $fieldName  = $configModel->getFieldName();
            $fieldType  = $configModel->getType();
            $configType = PropertyConfigContainer::TYPE_FIELD;

            /**
             * Add read only field name and field type
             */
            $builder->add(
                'fieldName',
                TextType::class,
                [
                    'label'     => 'oro.entity_config.form.name.label',
                    'block'     => 'general',
                    'disabled'  => true,
                    'data'      => $fieldName,
                ]
            );
            $builder->add(
                'type',
                ChoiceType::class,
                [
                    'label'       => 'oro.entity_config.form.type.label',
                    'choices'     => [],
                    'block'       => 'general',
                    'disabled'    => true,
                    'placeholder' => 'oro.entity_extend.form.data_type.' . $fieldType
                ]
            );
        } else {
            $className  = $configModel->getClassName();
            $fieldName  = null;
            $fieldType  = null;
            $configType = PropertyConfigContainer::TYPE_ENTITY;
        }

        foreach ($this->configManager->getProviders() as $provider) {
            if ($provider->getPropertyConfig()->hasForm($configType, $fieldType)) {
                $config = $this->configManager->getConfig($provider->getId($className, $fieldName, $fieldType));

                $builder->add(
                    $provider->getScope(),
                    ConfigScopeType::class,
                    [
                        'items' => $provider->getPropertyConfig()->getFormItems($configType, $fieldType),
                        'config' => $config,
                        'config_model' => $configModel,
                        'block_config' => $this->getFormBlockConfig($provider, $configType)
                    ]
                );
                $data[$provider->getScope()] = $config->all();
            }
        }

        $builder->add(self::PARTIAL_SUBMIT, SubmitType::class, ['validate' => false, 'validation_groups' => false]);

        $builder->setData($data);

        $builder->addEventSubscriber(
            new ConfigSubscriber(
                $this->translationHelper,
                $this->configManager,
                $this->translator
            )
        );
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        // Partial submit button should not be rendered.
        $view[self::PARTIAL_SUBMIT]->setRendered();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['config_model']);
        $resolver->setAllowedTypes('config_model', 'Oro\Bundle\EntityConfigBundle\Entity\ConfigModel');
        $resolver->setDefault('field_name', function (Options $options) {
            $configModel = $options['config_model'];
            if ($configModel instanceof FieldConfigModel && !$configModel->getId()) {
                return $configModel->getFieldName();
            }

            return '';
        });
        $resolver->setAllowedTypes('field_name', ['string', 'null']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_entity_config_type';
    }

    /**
     * @param ConfigProvider $configProvider
     * @param string         $configType
     * @return array
     */
    protected function getFormBlockConfig(ConfigProvider $configProvider, $configType)
    {
        $result = (array)$configProvider->getPropertyConfig()->getFormBlockConfig($configType);

        $this->applyFormBlockConfigTranslations($result);

        return $result;
    }

    protected function applyFormBlockConfigTranslations(array &$config)
    {
        foreach ($config as $key => &$val) {
            if (is_array($val)) {
                $this->applyFormBlockConfigTranslations($val);
            } elseif (is_string($val) && $key === 'title' && !empty($val)) {
                $val = $this->translator->trans($val);
            }
        }
    }
}
