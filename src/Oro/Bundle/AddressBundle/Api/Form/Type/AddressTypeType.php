<?php

namespace Oro\Bundle\AddressBundle\Api\Form\Type;

use Oro\Bundle\AddressBundle\Api\Form\DataTransformer\AddressTypeToIdTransformer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for a string field contains the identifier of address type
 * and mapped to an entity field contains Oro\Bundle\AddressBundle\Entity\AddressType object.
 */
class AddressTypeType extends AbstractType
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(
            new AddressTypeToIdTransformer($this->doctrineHelper)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['compound' => false]);
    }
}
