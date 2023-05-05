<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Form\DataTransformer\NestedAssociationTransformer;
use Oro\Bundle\ApiBundle\Form\EventListener\NestedAssociationListener;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityLoader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The form type for manageable entity nested associations.
 */
class NestedAssociationType extends AbstractType
{
    private PropertyAccessorInterface $propertyAccessor;
    private DoctrineHelper $doctrineHelper;
    private EntityLoader $entityLoader;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        DoctrineHelper $doctrineHelper,
        EntityLoader $entityLoader
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->doctrineHelper = $doctrineHelper;
        $this->entityLoader = $entityLoader;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addEventSubscriber(new NestedAssociationListener($this->propertyAccessor, $options['config']))
            ->addViewTransformer(
                new NestedAssociationTransformer($this->doctrineHelper, $this->entityLoader, $options['metadata'])
            );
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('compound', false)
            ->setDefault('multiple', true)
            ->setRequired(['metadata', 'config'])
            ->setAllowedTypes('metadata', [AssociationMetadata::class])
            ->setAllowedTypes('config', [EntityDefinitionFieldConfig::class]);
    }
}
