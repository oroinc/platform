<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Bridge\Doctrine\Form\EventListener\MergeDoctrineCollectionListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ApiBundle\Form\DataTransformer\CollectionToArrayTransformer;
use Oro\Bundle\ApiBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;

class EntityType extends AbstractType
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var AssociationMetadata $metadata */
        $metadata = $options['metadata'];
        if ($metadata->isCollection()) {
            $builder
                ->addEventSubscriber(new MergeDoctrineCollectionListener())
                ->addViewTransformer(
                    new CollectionToArrayTransformer(new EntityToIdTransformer($this->doctrine, $metadata)),
                    true
                );
        } else {
            $builder->addViewTransformer(new EntityToIdTransformer($this->doctrine, $metadata));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(['compound' => false])
            ->setRequired(['metadata'])
            ->setAllowedTypes('metadata', ['Oro\Bundle\ApiBundle\Metadata\AssociationMetadata']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_api_entity';
    }
}
