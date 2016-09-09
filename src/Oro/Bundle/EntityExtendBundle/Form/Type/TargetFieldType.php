<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class TargetFieldType extends AbstractType
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var string|null */
    protected $entityClass;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
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
                'choices'         => $this->getFieldChoiceList(),
                'auto_initialize' => false
            ]
        );
    }

    /**
     * @return array
     */
    protected function getFieldChoiceList()
    {
        $choices = [];

        if (!$this->entityClass) {
            return $choices;
        }

        $entityConfigProvider = $this->configManager->getProvider('entity');
        $fieldConfigs         = $this->configManager->getProvider('extend')->getConfigs($this->entityClass);
        foreach ($fieldConfigs as $fieldConfig) {
            /** @var FieldConfigId $fieldId */
            $fieldId = $fieldConfig->getId();
            if (!in_array(
                $fieldId->getFieldType(),
                ['integer', 'string', 'smallint', 'decimal', 'bigint', 'text', 'money'],
                true
            )) {
                continue;
            }
            if ($fieldConfig->is('is_deleted')) {
                continue;
            }

            $fieldLabel = $entityConfigProvider
                ->getConfig($fieldId->getClassName(), $fieldId->getFieldName())
                ->get('label');

            $choices[$fieldId->getFieldName()] = $fieldLabel ?: $fieldId->getFieldName();
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
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_entity_target_field_type';
    }
}
