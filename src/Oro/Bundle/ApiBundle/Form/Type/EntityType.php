<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Form\DataTransformer\CollectionToArrayTransformer;
use Oro\Bundle\ApiBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Util\EntityLoader;
use Symfony\Bridge\Doctrine\Form\EventListener\MergeDoctrineCollectionListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityType extends AbstractType
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var EntityLoader */
    protected $entityLoader;

    /**
     * @param ManagerRegistry $doctrine
     * @param EntityLoader    $entityLoader
     */
    public function __construct(ManagerRegistry $doctrine, EntityLoader $entityLoader)
    {
        $this->doctrine = $doctrine;
        $this->entityLoader = $entityLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var AssociationMetadata $metadata */
        $metadata = $options['metadata'];
        /** @var IncludedEntityCollection|null $includedEntities */
        $includedEntities = $options['included_entities'];
        if ($metadata->isCollection()) {
            $builder
                ->addEventSubscriber(new MergeDoctrineCollectionListener())
                ->addViewTransformer(
                    new CollectionToArrayTransformer(
                        new EntityToIdTransformer($this->doctrine, $this->entityLoader, $metadata, $includedEntities)
                    ),
                    true
                );
        } else {
            $builder->addViewTransformer(
                new EntityToIdTransformer($this->doctrine, $this->entityLoader, $metadata, $includedEntities)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(['compound' => false, 'included_entities' => null])
            ->setRequired(['metadata'])
            ->setAllowedTypes('metadata', [AssociationMetadata::class])
            ->setAllowedTypes('included_entities', ['null', IncludedEntityCollection::class]);
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
        return 'oro_api_entity';
    }
}
