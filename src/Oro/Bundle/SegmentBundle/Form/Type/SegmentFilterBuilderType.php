<?php

namespace Oro\Bundle\SegmentBundle\Form\Type;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType as SegmentTypeEntity;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * SegmentFilterBuilderType is responsible for segment management functionality embedding into other forms.
 * Only Filters section is shown to user and could be changed. All other options required for segment creation should
 * be passed as form type options.
 *
 * Options:
 *  segment_entity - required segment entity class name
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
    public function getName()
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
        $resolver->setRequired('segment_entity');

        $resolver->setAllowedTypes('segment_entity', 'string');
        $resolver->setAllowedTypes('segment_type', 'string');
        $resolver->setAllowedTypes('segment_name_template', 'string');
        $resolver->setAllowedTypes('segment_columns', ['array', 'null']);
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
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('definition', HiddenType::class, ['required' => false]);
        $builder->add('entity', HiddenType::class, ['required' => false, 'data' => $options['segment_entity']]);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                // Remove owner field if was is automatically added, as owner is set in POST_SUBMIT
                $event->getForm()->remove('owner');
            }
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                /** @var Segment $segment */
                $segment = $event->getData();
                if ($segment) {
                    $config = $event->getForm()->getConfig();
                    if (!$segment->getId()) {
                        $segmentTypeName = $config->getOption('segment_type');
                        /** @var SegmentTypeEntity $segmentType */
                        $segmentType = $this->doctrineHelper
                            ->getEntityReference(SegmentTypeEntity::class, $segmentTypeName);
                        $segment->setType($segmentType);
                        $segment->setName(sprintf($config->getOption('segment_name_template'), uniqid('#', false)));

                        /** @var User $user */
                        $user = $this->tokenStorage->getToken()->getUser();
                        $segment->setOwner($user->getOwner());
                        $segment->setOrganization($user->getOrganization());
                    }

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
            }
        );
    }
}
