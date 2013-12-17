<?php

namespace Oro\Bundle\AddressBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;

class AddressType extends AbstractType
{
    /**
     * @var AddressCountryAndRegionSubscriber
     */
    private $countryAndRegionSubscriber;

    /**
     * @param AddressCountryAndRegionSubscriber $eventListener
     */
    public function __construct(AddressCountryAndRegionSubscriber $eventListener)
    {
        $this->countryAndRegionSubscriber = $eventListener;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->countryAndRegionSubscriber);

        $builder
            ->add('id', 'hidden')
            ->add('label', 'text', array('required' => false))
            ->add('namePrefix', 'text', array('required' => false))
            ->add('firstName', 'text', array('required' => false))
            ->add('middleName', 'text', array('required' => false))
            ->add('lastName', 'text', array('required' => false))
            ->add('nameSuffix', 'text', array('required' => false))
            ->add('organization', 'text', array('required' => false))
            ->add('country', 'oro_country', array('required' => true))
            ->add('street', 'text', array('required' => true))
            ->add('street2', 'text', array('required' => false))
            ->add('city', 'text', array('required' => true))
            ->add('state', 'oro_region', array('required' => false))
            ->add('state_text', 'hidden', array('required' => false))
            ->add('postalCode', 'text', array('required' => true, 'label' => 'ZIP/Postal code'));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'           => 'Oro\Bundle\AddressBundle\Entity\Address',
                'intention'            => 'address',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                'single_form'          => true
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_address';
    }
}
