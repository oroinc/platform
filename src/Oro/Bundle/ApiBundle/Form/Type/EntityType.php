<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Bridge\Doctrine\Form\EventListener\MergeDoctrineCollectionListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ApiBundle\Form\DataTransformer\CollectionToArrayTransformer;
use Oro\Bundle\ApiBundle\Form\DataTransformer\EntityToIdTransformer;

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
        if ($options['multiple']) {
            $builder
                ->addEventSubscriber(new MergeDoctrineCollectionListener())
                ->addViewTransformer(
                    new CollectionToArrayTransformer(new EntityToIdTransformer($this->doctrine)),
                    true
                );
        } else {
            $builder->addViewTransformer(new EntityToIdTransformer($this->doctrine));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(
                [
                    'multiple' => false,
                    'compound' => false
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_api_entity';
    }
}
