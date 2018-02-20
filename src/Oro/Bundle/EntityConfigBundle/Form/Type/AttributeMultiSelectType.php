<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\DataTransformer\AttributeRelationsTransformer;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttributeMultiSelectType extends AbstractType
{
    const NAME = 'oro_entity_config_attribute_multi_select';

    /** @var array */
    private $choices = [];

    /** @var array */
    private $configFields = [];

    /** @var AttributeManager */
    private $attributeManager;

    /**
     * @param AttributeManager $attributeManager
     */
    public function __construct(AttributeManager $attributeManager)
    {
        $this->attributeManager = $attributeManager;
    }

    /**
     * @param $entityClass
     * @return array
     */
    private function getChoices($entityClass)
    {
        if (!$this->choices) {
            $fields = $this->attributeManager->getActiveAttributesByClass($entityClass);

            /** @var FieldConfigModel $field */
            foreach ($fields as $field) {
                $this->choices[$field->getId()] = $this->attributeManager->getAttributeLabel($field);
                $this->configFields[$field->getId()] = $field;
            }
        }

        return $this->choices;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new AttributeRelationsTransformer($options['attributeGroup']));
    }

    /**
     * @param int $choiceId
     * @return bool
     */
    private function isSystem($choiceId)
    {
        $field = $this->configFields[$choiceId];

        return $this->attributeManager->isSystem($field);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'attributeEntityClass' => '',
                'attributeGroup' => null,
                'choices' => function (Options $options) {
                    return $this->getChoices($options['attributeEntityClass']);
                },
                'choice_attr' => function ($choice) {
                    return [
                        'locked' => ($this->isSystem($choice)) ? 'locked' : ''
                    ];
                },
                'multiple' => true,
                'configs' => [
                    'component' => 'attribute-autocomplete',
                ],
                'attr' => [
                    'class' => 'attribute-select',
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_select2_choice';
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
        return self::NAME;
    }
}
