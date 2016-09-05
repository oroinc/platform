<?php

namespace Oro\Bundle\AddressBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\EventListener\FixAddressesPrimarySubscriber;
use Oro\Bundle\AddressBundle\Form\EventListener\FixAddressesTypesSubscriber;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TypedAddressType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['single_form'] && $options['all_addresses_property_path']) {
            $builder->addEventSubscriber(
                new FixAddressesPrimarySubscriber($options['all_addresses_property_path'])
            );
            $builder->addEventSubscriber(
                new FixAddressesTypesSubscriber($options['all_addresses_property_path'])
            );
        }

        $builder
            ->add(
                'types',
                'translatable_entity',
                array(
                    'class'    => 'OroAddressBundle:AddressType',
                    'property' => 'label',
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true
                )
            )
            ->add(
                'primary',
                'checkbox',
                array(
                    'required' => false
                )
            );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\Bundle\AddressBundle\Entity\AbstractTypedAddress',
                'all_addresses_property_path' => 'owner.addresses'
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_address';
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
        return 'oro_typed_address';
    }
}
