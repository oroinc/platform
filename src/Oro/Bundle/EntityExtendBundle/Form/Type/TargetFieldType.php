<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class TargetFieldType extends AbstractType
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var string
     */
    protected $entityClass;

    public function __construct(ConfigManager $configManager, $entityClass)
    {
        $this->configManager = $configManager;
        $this->entityClass   = $entityClass;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'attr'            => array('class' => 'extend-rel-target-field'),
                'label'           => 'oro.entity_extend.form.target_field',
                'empty_value'     => 'oro.entity.form.choose_entity_field',
                'choices'         => $this->getPropertyChoiceList(),
                'auto_initialize' => false
            )
        );
    }

    /**
     * @return array
     */
    protected function getPropertyChoiceList()
    {
        $choices = array();

        if (!$this->entityClass) {
            return $choices;
        }

        $fields = $this->configManager->getProvider('extend')->filter(
            function (Config $config) {
                return
                    in_array(
                        $config->getId()->getFieldType(),
                        ['integer', 'string', 'smallint', 'decimal', 'bigint', 'text', 'money']
                    )
                    && $config->is('is_deleted', false);
            },
            $this->entityClass
        );

        $entityConfigProvider = $this->configManager->getProvider('entity');
        foreach ($fields as $field) {
            $label = $entityConfigProvider->getConfigById($field->getId())->get('label');

            $choices[$field->getId()->getFieldName()] = $label ? : $field->getId()->getFieldName();
        }

        return $choices;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_target_field_type';
    }
}
