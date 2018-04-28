<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType as RelationTypeBase;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\NonExtendedEntityBidirectional;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RelationType extends AbstractType
{
    const ALLOWED_BIDIRECTIONAL_RELATIONS = [
        \Oro\Bundle\EntityExtendBundle\Extend\RelationType::MANY_TO_ONE,
        \Oro\Bundle\EntityExtendBundle\Extend\RelationType::MANY_TO_MANY,
        \Oro\Bundle\EntityExtendBundle\Extend\RelationType::ONE_TO_MANY,
    ];

    /** @var ConfigManager */
    protected $configManager;

    /** @var Config */
    protected $config;

    /** @var FormFactory */
    protected $formFactory;

    /**
     * @param ConfigManager   $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager   = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->config      = $this->configManager
            ->getProvider('extend')
            ->getConfigById($options['config_id']);
        $this->formFactory = $builder->getFormFactory();

        $this->addTargetEntityField($builder, $options);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSubmitData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmitData']);
    }

    /**
     * @param FormEvent $event
     * @param string $eventName
     */
    public function preSubmitData(FormEvent $event, $eventName)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (!$data) {
            $data = $form->getParent()->getData();
        }

        if ($this->config->get('owner') === ExtendScope::OWNER_CUSTOM) {
            $this->addBidirectionalField($form, $data);

            $targetEntity = $this->getArrayValue($data, 'target_entity');
            $relationType = $this->config->getId()->getFieldType();

            if ($relationType == RelationTypeBase::MANY_TO_ONE) {
                $this->addTargetField(
                    $form,
                    'target_field',
                    $targetEntity,
                    $this->getArrayValue($data, 'target_field')
                );
            } else {
                $this->addTargetField(
                    $form,
                    'target_grid',
                    $targetEntity,
                    $this->getArrayValue($data, 'target_grid'),
                    'oro.entity_extend.form.relation.entity_data_fields',
                    true
                );
                $this->addTargetField(
                    $form,
                    'target_title',
                    $targetEntity,
                    $this->getArrayValue($data, 'target_title'),
                    'oro.entity_extend.form.relation.entity_info_title',
                    true
                );
                $this->addTargetField(
                    $form,
                    'target_detailed',
                    $targetEntity,
                    $this->getArrayValue($data, 'target_detailed'),
                    'oro.entity_extend.form.relation.entity_detailed',
                    true
                );
            }
        }

        if ($eventName == FormEvents::PRE_SUBMIT) {
            $form->getParent()->setData(array_merge($form->getParent()->getData(), $data));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'mapped' => false,
                'label'  => false,
                'constraints' => [new NonExtendedEntityBidirectional()]
            ]
        );
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
        return 'oro_entity_relation_type';
    }

    /**
     * @param FormInterface $form
     * @param string        $name
     * @param string        $targetEntityClass
     * @param array|null    $data
     * @param string|null   $label
     * @param bool          $multiple
     */
    protected function addTargetField(
        FormInterface $form,
        $name,
        $targetEntityClass,
        $data = null,
        $label = null,
        $multiple = false
    ) {
        $options                = [];
        $options['constraints'] = [new Assert\NotBlank()];
        if ($label) {
            $options['label'] = $label;
        }
        if ($multiple) {
            $options['multiple'] = true;
        }

        $options['entityClass'] = $targetEntityClass;

        $form->add(
            $this->formFactory->createNamed(
                $name,
                TargetFieldType::class,
                $data,
                $options
            )
        );
    }

    /**
     * @param array  $data
     * @param string $key
     * @param mixed  $defaultValue
     * @return mixed
     */
    protected function getArrayValue(array &$data, $key, $defaultValue = null)
    {
        return isset($data[$key])
            ? $data[$key]
            : $defaultValue;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    private function addTargetEntityField(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'target_entity',
            TargetType::class,
            [
                'field_config_id' => $options['config_id'],
                'constraints' => [new Assert\NotBlank()]
            ]
        );
    }

    /**
     * @param FormInterface $form
     * @param array|null $data
     */
    private function addBidirectionalField(FormInterface $form, array $data = null)
    {
        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $this->config->getId();

        // readonly when updating field (so bidirectional option already exists)
        $readOnly = $this->config->get('bidirectional') !== null;

        // if reusing relation ("Reuse existing relation" option on UI) or for one2many relation
        // we would have always bidirectional relations
        $reusedExistingRelation = $this->config->get('state') === 'New' && $this->config->get('relation_key');
        if ($reusedExistingRelation || $fieldConfigId->getFieldType() === RelationTypeBase::ONE_TO_MANY) {
            $readOnly = true;
            $data['bidirectional'] = true;
        }

        $attr = [];

        if ($readOnly) {
            $attr['readonly'] = true;
        }

        if (in_array($fieldConfigId->getFieldType(), static::ALLOWED_BIDIRECTIONAL_RELATIONS, true)) {
            $options = [
                'choices' => [
                    'No' => 0,
                    'Yes' => 1,
                ],
                'placeholder' => false,
                'block' => 'general',
                'subblock' => 'properties',
                'label' => 'oro.entity_extend.entity_config.extend.field.items.bidirectional',
                'data' => $this->getArrayValue($data, 'bidirectional'),
                'attr' => $attr
            ];

            $form->add('bidirectional', Select2ChoiceType::class, $options);
        }
    }
}
