<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TargetFieldType extends AbstractType
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('entityClass');
        $resolver->setAllowedTypes('entityClass', ['string', 'null']);

        $resolver->setDefaults(
            [
                'entityClass'     => null,
                'attr'            => ['class' => 'extend-rel-target-field'],
                'label'           => 'oro.entity_extend.form.target_field',
                'placeholder'     => 'oro.entity.form.choose_entity_field',
                'auto_initialize' => false
            ]
        );

        $resolver->setNormalizer('choices', function (Options $options) {
            return $this->getFieldChoiceList($options['entityClass']);
        });
    }

    /**
     * @param string|null $entityClass
     * @return array
     */
    protected function getFieldChoiceList($entityClass)
    {
        $choices = [];

        if (!$entityClass) {
            return $choices;
        }

        $entityConfigProvider = $this->configManager->getProvider('entity');
        $fieldConfigs         = $this->configManager->getProvider('extend')->getConfigs($entityClass);
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
            $fieldName = $fieldId->getFieldName();

            $choices[$fieldLabel ?: $fieldName] = $fieldName;
        }

        return $choices;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
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
