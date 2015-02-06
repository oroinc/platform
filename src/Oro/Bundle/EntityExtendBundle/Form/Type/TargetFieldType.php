<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class TargetFieldType extends AbstractType
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var string|null */
    protected $entityClass = null;

    /** @var EntityFieldProvider */
    protected $entityFieldProvider;

    /**
     * @param ConfigManager       $configManager
     * @param EntityFieldProvider $entityFieldProvider
     */
    public function __construct(ConfigManager $configManager, EntityFieldProvider $entityFieldProvider)
    {
        $this->configManager       = $configManager;
        $this->entityFieldProvider = $entityFieldProvider;
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'attr'            => ['class' => 'extend-rel-target-field'],
                'label'           => 'oro.entity_extend.form.target_field',
                'empty_value'     => 'oro.entity.form.choose_entity_field',
                'choices'         => $this->getPropertyChoiceList(),
                'auto_initialize' => false
            ]
        );
    }

    /**
     * @return array
     */
    protected function getPropertyChoiceList()
    {
        $choices = [];

        if (!$this->entityClass) {
            return $choices;
        }

        $fields = $this->entityFieldProvider->getFields($this->entityClass);
        foreach ($fields as $field) {
            if (!in_array(
                $field['type'],
                ['integer', 'string', 'smallint', 'decimal', 'bigint', 'text', 'money']
            )) {
                continue;
            }

            $choices[$field['name']] = $field['label'] ? : $field['name'];
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
