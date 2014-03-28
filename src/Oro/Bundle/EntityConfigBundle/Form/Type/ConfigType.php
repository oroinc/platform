<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Oro\Bundle\TranslationBundle\Translation\Translator;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\OptionSet;
use Oro\Bundle\EntityConfigBundle\Form\EventListener\ConfigSubscriber;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;

class ConfigType extends AbstractType
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
     * @var DynamicTranslationMetadataCache
     */
    protected $dbTranslationMetadataCache;

    /**
     * @param ConfigManager $configManager
     * @param Translator    $translator
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
                    'block'     => 'entity',
                    'disabled'  => true,
                    'data'      => $fieldName,
                )
            );
            $builder->add(
                'type',
                'choice',
                array(
                    'choices'     => [],
                    'block'       => 'entity',
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
                        'block_config' => (array)$provider->getPropertyConfig()->getFormBlockConfig($configType)
                    )
                );
                $data[$provider->getScope()] = $config->all();
            }
        }

        if ($fieldType == 'optionSet') {
            $data['extend']['set_options'] = $this->configManager->getEntityManager()
                ->getRepository(OptionSet::ENTITY_NAME)
                ->findOptionsByField($configModel->getId());
        }

        $builder->setData($data);

        $builder->addEventSubscriber(
            new ConfigSubscriber(
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
                'config_model' => 'Oro\Bundle\EntityConfigBundle\Entity\AbstractConfigModel'
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
}
