<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;

/**
 * This form type may be used to adapt any form type to be used as root form type for REST and SOAP API
 *
 * Example of usage:
 * <code>
 * user.form.type.api:
 *     parent: oro_form.type.api
 *     arguments:
 *         - user_api # form type name (must be equal to 'alias' attribute of 'form.type' tag)
 *         - @user.form.type # decorated form type service
 *     tags:
 *         - { name: form.type, alias: user_api }
 * </code>
 */
class DecoratorApiType extends AbstractType
{
    /**
     * @var string
     */
    protected $typeName;

    /**
     * @var FormTypeInterface
     */
    protected $parentType;

    /**
     * @param string            $typeName
     * @param FormTypeInterface $parentType
     */
    public function __construct($typeName, FormTypeInterface $parentType)
    {
        $this->typeName   = $typeName;
        $this->parentType = $parentType;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->parentType->buildForm($builder, $options);

        $builder->addEventSubscriber(new PatchSubscriber());
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->parentType->setDefaultOptions($resolver);

        $resolver->setDefaults(
            [
                'cascade_validation' => true,
                'csrf_protection'    => false
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->parentType->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->typeName;
    }
}
