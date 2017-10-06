<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Form\DataTransformer\NestedAssociationTransformer;
use Oro\Bundle\ApiBundle\Form\EventListener\NestedAssociationListener;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Util\EntityLoader;

class NestedAssociationType extends AbstractType
{
    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var EntityLoader */
    protected $entityLoader;

    /**
     * @param PropertyAccessorInterface $propertyAccessor
     * @param ManagerRegistry           $doctrine
     * @param EntityLoader              $entityLoader
     */
    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        ManagerRegistry $doctrine,
        EntityLoader $entityLoader
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->doctrine = $doctrine;
        $this->entityLoader = $entityLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventSubscriber(new NestedAssociationListener($this->propertyAccessor, $options['config']))
            ->addViewTransformer(
                new NestedAssociationTransformer($this->doctrine, $this->entityLoader, $options['metadata'])
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(['compound' => false])
            ->setRequired(['metadata', 'config'])
            ->setAllowedTypes('metadata', [AssociationMetadata::class])
            ->setAllowedTypes('config', [EntityDefinitionFieldConfig::class]);
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
        return 'oro_api_nested_association';
    }
}
