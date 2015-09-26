<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\EventListener\ConfigSubscriber;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class ConfigType extends AbstractType
{
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
                'text',
                array(
                    'label'     => 'oro.entity_config.form.name.label',
                    'block'     => 'general',
                    'disabled'  => true,
                    'data'      => $fieldName,
                )
            );
            $builder->add(
                'type',
                'choice',
                array(
                    'label'       => 'oro.entity_config.form.type.label',
                    'choices'     => [],
                    'block'       => 'general',
                    'disabled'    => true,
                    'empty_value' => 'oro.entity_extend.form.data_type.' . $fieldType
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
                    new ConfigScopeType(
                        $provider->getPropertyConfig()->getFormItems($configType, $fieldType),
                        $config,
                        $this->configManager,
                        $configModel
                    ),
                    array(
                        'block_config' => $this->getFormBlockConfig($provider, $configType)
                    )
                );
                $data[$provider->getScope()] = $config->all();
            }
        }

        $builder->setData($data);

        $builder->addEventSubscriber(
            new ConfigSubscriber(
                $this->doctrine,
                $this->configManager,
                $this->translator,
                $this->dbTranslationMetadataCache
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('config_model'));

        $resolver->setAllowedTypes(
            array(
                'config_model' => 'Oro\Bundle\EntityConfigBundle\Entity\ConfigModel'
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
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
