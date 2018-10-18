<?php

namespace Oro\Bundle\SegmentBundle\Form\Type;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\QueryDesignerBundle\Validator\NotBlankFilters;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType as SegmentTypeEntity;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * SegmentFilterBuilderType is responsible for segment management functionality embedding into other forms.
 * Only Filters section is shown to user and could be changed. All other options required for segment creation should
 * be passed as form type options.
 *
 * Options:
 *  segment_entity - required segment entity class name
 *  add_name_field - optional boolean flag (false by default) responsible for segment name field presence on form
 *  name_field_required - optional boolean flag (false by default) to configure segment name as required
 *  segment_type - optional string, one of SegmentType::TYPE_DYNAMIC (default) or SegmentTypeEntity::TYPE_STATIC
 *  segment_columns - optional array of segment columns. If empty entity identifiers is added by default
 *  segment_name_template - optional string of segment name in sprintf syntax. Should contain one %s placeholder
 *                          "Auto generated segment %s" set by default
 */
class SegmentFilterBuilderType extends AbstractType
{
    const NAME = 'oro_segment_filter_builder';

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        TokenStorageInterface $tokenStorage
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', Segment::class);
        $resolver->setDefault('segment_type', SegmentTypeEntity::TYPE_DYNAMIC);
        $resolver->setDefault('segment_columns', null);
        $resolver->setDefault('segment_name_template', 'Auto generated segment %s');
        $resolver->setDefault('add_name_field', false);
        $resolver->setDefault('name_field_required', false);
        $resolver->setDefault('attr', ['data-role' => 'query-designer-container']);
        $resolver->setDefault('field_event_listeners', null);
        $resolver->setDefault('condition_builder_validation', [
            'condition-item' => [
                'NotBlank' => ['message' => 'oro.query_designer.condition_builder.condition_item.not_blank'],
            ],
            'conditions-group' => [
                'NotBlank' => ['message' => 'oro.query_designer.condition_builder.conditions_group.not_blank'],
            ],
        ]);
        $resolver->setRequired('segment_entity');

        $resolver->setAllowedTypes('segment_entity', 'string');
        $resolver->setAllowedTypes('segment_type', 'string');
        $resolver->setAllowedTypes('segment_name_template', 'string');
        $resolver->setAllowedTypes('add_name_field', 'bool');
        $resolver->setAllowedTypes('name_field_required', 'bool');
        $resolver->setAllowedTypes('segment_columns', ['array', 'null']);
        $resolver->setAllowedTypes('field_event_listeners', ['array', 'null']);
        $resolver->setAllowedValues(
            'segment_type',
            [SegmentTypeEntity::TYPE_DYNAMIC, SegmentTypeEntity::TYPE_STATIC]
        );

        $resolver->setNormalizer(
            'segment_entity',
            function (Options $options, $value) {
                if (!$this->doctrineHelper->getEntityManagerForClass($value, false)) {
                    throw new InvalidOptionsException(
                        sprintf('Option segment_entity must be a valid entity class, "%s" given', $value)
                    );
                }

                return $value;
            }
        );

        $resolver->setNormalizer(
            'segment_columns',
            function (Options $options, $value) {
                if (!$value) {
                    $value = [$this->doctrineHelper->getSingleEntityIdentifierFieldName($options['segment_entity'])];
                }

                return $value;
            }
        );

        $resolver->setNormalizer(
            'constraints',
            function (Options $options, $value) {
                if ($options['required']) {
                    $hasNotBlankFiltersConstraint = false;
                    if ($value && !is_array($value)) {
                        $value = [$value];
                    }
                    foreach ((array)$value as $constraint) {
                        if ($constraint instanceof NotBlankFilters) {
                            $hasNotBlankFiltersConstraint = true;
                            break;
                        }
                    }
                    if (!$hasNotBlankFiltersConstraint) {
                        $value[] = new NotBlankFilters();
                    }
                }

                return $value;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('definition', HiddenType::class, ['required' => false]);
        $builder->add('entity', HiddenType::class, ['required' => false, 'data' => $options['segment_entity']]);

        if ($options['field_event_listeners']) {
            foreach ($options['field_event_listeners'] as $field => $listeners) {
                foreach ($listeners as $event => $listener) {
                    $builder->get($field)->addEventListener($event, $listener);
                }
            }
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        // Remove owner field if was is automatically added, as owner is set in POST_SUBMIT
        $event->getForm()->remove('owner');

        $config = $event->getForm()->getConfig();
        if ($config->getOption('add_name_field')) {
            $segment = $event->getData();
            $isNameRequired = $config->getOption('name_field_required');
            $nameFieldOptions = [
                'required' => $isNameRequired,
                'mapped' => false,
                'label' => 'oro.segment.segment_filter_builder.segment_name'
            ];
            if ($segment instanceof Segment) {
                $nameFieldOptions['data'] = $segment->getName();
            }
            if ($isNameRequired) {
                $nameFieldOptions['constraints'][] = new NotBlank();
            }

            $event->getForm()->add('name', TextType::class, $nameFieldOptions);
        }
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var Segment $segment */
        $segment = $event->getData();
        $form = $event->getForm();
        if ($segment) {
            $config = $event->getForm()->getConfig();
            if (!$segment->getId()) {
                $segmentTypeName = $config->getOption('segment_type');
                /** @var SegmentTypeEntity $segmentType */
                $segmentType = $this->doctrineHelper
                    ->getEntityReference(SegmentTypeEntity::class, $segmentTypeName);
                $segment->setType($segmentType);

                $user = $this->tokenStorage->getToken()->getUser();
                if ($user instanceof User) {
                    $segment->setOwner($user->getOwner());
                    $segment->setOrganization($user->getOrganization());
                }
            }

            $this->setSegmentName($segment, $config, $form);
            $this->setSegmentDefinition($segment, $config);
        }
    }

    /**
     * @param Segment $segment
     * @param FormConfigInterface $config
     */
    private function setSegmentDefinition(Segment $segment, FormConfigInterface $config)
    {
        $definition = json_decode($segment->getDefinition(), true);
        foreach ((array)$config->getOption('segment_columns') as $column) {
            // Check for column existence and skip adding if found
            if (isset($definition['columns']) && is_array($definition['columns'])) {
                foreach ($definition['columns'] as $columnDefinition) {
                    if (isset($columnDefinition['name']) && $columnDefinition['name'] === $column) {
                        continue 2;
                    }
                }
            }

            $definition['columns'][] = [
                'name' => $column,
                'label' => $column,
                'sorting' => null,
                'func' => null
            ];
        }
        $segment->setDefinition(json_encode($definition));
    }

    /**
     * @param Segment $segment
     * @param FormConfigInterface $config
     * @param FormInterface $form
     */
    private function setSegmentName(Segment $segment, FormConfigInterface $config, FormInterface $form)
    {
        $segmentName = null;
        if ($form->has('name')) {
            $segmentName = $form->get('name')->getData();
        }
        if (!$segmentName && !$config->getOption('name_field_required') && empty($segment->getName())) {
            $segmentName = sprintf($config->getOption('segment_name_template'), uniqid('#', false));
        }

        if ($segmentName) {
            $segment->setName($segmentName);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['condition_builder_options'] = [
            'validation' => $options['condition_builder_validation'],
        ];
    }
}
