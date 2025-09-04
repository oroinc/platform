<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\DataTransformer\AttributeRelationsTransformer;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides multiselect for collection of attributes
 */
class AttributeMultiSelectType extends AbstractType
{
    const NAME = 'oro_entity_config_attribute_multi_select';

    /** @var array */
    private $choices = [];

    /** @var array */
    private $configFields = [];

    /** @var AttributeManager */
    private $attributeManager;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(AttributeManager $attributeManager, TranslatorInterface $translator)
    {
        $this->attributeManager = $attributeManager;
        $this->translator = $translator;
    }

    /**
     * @param $entityClass
     * @return array
     */
    private function getChoices($entityClass)
    {
        if (!$this->choices) {
            $fields = $this->attributeManager->getActiveAttributesByClass($entityClass);
            $existingLabels = [];
            /** @var FieldConfigModel $field */
            foreach ($fields as $field) {
                $attributeFieldLabel = $this->attributeManager->getAttributeLabel($field);
                $this->configFields[$field->getId()] = $field;

                if (\in_array($attributeFieldLabel, $existingLabels, true)) {
                    if (\array_key_exists($attributeFieldLabel, $this->choices)) {
                        $updatedLabel = sprintf(
                            '%s(%s)',
                            $attributeFieldLabel,
                            $this->getFieldNameByFieldId($this->choices[$attributeFieldLabel])
                        );
                        $this->choices[$updatedLabel] = $this->choices[$attributeFieldLabel];
                        unset($this->choices[$attributeFieldLabel]);
                    }
                    $attributeFieldLabel = sprintf(
                        '%s(%s)',
                        $attributeFieldLabel,
                        $this->getFieldNameByFieldId($field->getId())
                    );
                }

                $this->choices[$attributeFieldLabel] = $field->getId();
                $existingLabels[] = $attributeFieldLabel;
            }
        }

        return $this->choices;
    }

    private function getFieldNameByFieldId(int $id): string
    {
        /** @var FieldConfigModel $field */
        $field = $this->configFields[$id];
        $fieldName = $field->getFieldName();
        $fieldAttributes = $field->toArray('attribute');

        if (!empty($fieldAttributes['field_name'])) {
            $fieldName = $fieldAttributes['field_name'];
        }

        if ($this->isSystem($id)) {
            $fieldName = sprintf('%s, %s', $fieldName, $this->translator->trans('oro.entity_config.attribute.system'));
        }

        return $fieldName;
    }

    #[\Override]
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

    #[\Override]
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

    #[\Override]
    public function getParent(): ?string
    {
        return Select2ChoiceType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
