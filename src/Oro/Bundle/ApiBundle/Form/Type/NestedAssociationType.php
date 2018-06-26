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
    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityLoader */
    protected $entityLoader;

    /**
     * @param PropertyAccessorInterface $propertyAccessor
     * @param DoctrineHelper            $doctrineHelper
     * @param EntityLoader              $entityLoader
     */
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
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventSubscriber(new NestedAssociationListener($this->propertyAccessor, $options['config']))
            ->addViewTransformer(
                new NestedAssociationTransformer($this->doctrineHelper, $this->entityLoader, $options['metadata'])
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
}
