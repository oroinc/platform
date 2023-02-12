<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Form\DataTransformer\CollectionToArrayTransformer;
use Oro\Bundle\ApiBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityLoader;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for manageable entity associations.
 */
class EntityType extends AbstractType
{
    private DoctrineHelper $doctrineHelper;
    private EntityLoader $entityLoader;

    public function __construct(DoctrineHelper $doctrineHelper, EntityLoader $entityLoader)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityLoader = $entityLoader;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AssociationMetadata $metadata */
        $metadata = $options['metadata'];
        /** @var EntityMapper|null $entityMapper */
        $entityMapper = $options['entity_mapper'];
        /** @var IncludedEntityCollection|null $includedEntities */
        $includedEntities = $options['included_entities'];
        if ($metadata->isCollection()) {
            $builder
                ->addViewTransformer(
                    new CollectionToArrayTransformer(
                        new EntityToIdTransformer(
                            $this->doctrineHelper,
                            $this->entityLoader,
                            $metadata,
                            $entityMapper,
                            $includedEntities
                        )
                    ),
                    true
                );
        } else {
            $builder->addViewTransformer(
                new EntityToIdTransformer(
                    $this->doctrineHelper,
                    $this->entityLoader,
                    $metadata,
                    $entityMapper,
                    $includedEntities
                )
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('compound', false)
            ->setDefault('multiple', true)
            ->setDefault('entity_mapper', null)
            ->setDefault('included_entities', null)
            ->setRequired(['metadata'])
            ->setAllowedTypes('metadata', [AssociationMetadata::class])
            ->setAllowedTypes('entity_mapper', ['null', EntityMapper::class])
            ->setAllowedTypes('included_entities', ['null', IncludedEntityCollection::class]);
    }
}
