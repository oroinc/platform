<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;

class AbstractApiType extends AbstractType
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
