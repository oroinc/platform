<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\EventListener\ConfigSubscriber;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigType extends AbstractType
{
    /** @var ConfigTranslationHelper */
    protected $translationHelper;

    /** @var ConfigManager */
    protected $configManager;

    /** @var Translator */
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $configModel = $options['config_model'];
        $data        = array();

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
                array(
                    'label'     => 'oro.entity_config.form.name.label',
                    'block'     => 'general',
                    'disabled'  => true,
                    'data'      => $fieldName,
                )
            );
            $builder->add(
                'type',
                ChoiceType::class,
                array(
                    'label'       => 'oro.entity_config.form.type.label',
                    'choices'     => [],
                    'block'       => 'general',
                    'disabled'    => true,
                    'placeholder' => 'oro.entity_extend.form.data_type.' . $fieldType
                )
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
                    array(
                        'items' => $provider->getPropertyConfig()->getFormItems($configType, $fieldType),
                        'config' => $config,
                        'config_model' => $configModel,
                        'config_manager' => $this->configManager,
                        'block_config' => $this->getFormBlockConfig($provider, $configType)
                    )
                );
                $data[$provider->getScope()] = $config->all();
            }
        }

        $builder->setData($data);

        $builder->addEventSubscriber(
            new ConfigSubscriber(
                $this->translationHelper,
                $this->configManager,
                $this->translator
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(array('config_model'));

        $resolver->setAllowedTypes('config_model', 'Oro\Bundle\EntityConfigBundle\Entity\ConfigModel');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
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

    /**
     * @param array $config
     */
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
