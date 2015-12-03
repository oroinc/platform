<?php

namespace Oro\Bundle\AddressBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ->add('id', HiddenType::class)
            ->add('label', TextType::class, array('required' => false, 'label' => 'Label'))
            ->add('namePrefix', TextType::class, array('required' => false, 'label' => 'Name Prefix'))
            ->add('firstName', TextType::class, array('required' => false, 'label' => 'First Name'))
            ->add('middleName', TextType::class, array('required' => false, 'label' => 'Middle Name'))
            ->add('lastName', TextType::class, array('required' => false, 'label' => 'Last Name'))
            ->add('nameSuffix', TextType::class, array('required' => false, 'label' => 'Name Suffix'))
            ->add('organization', TextType::class, array('required' => false, 'label' => 'Organization'))
            ->add('country', 'oro_country', array('required' => true, 'label' => 'Country'))
            ->add('street', TextType::class, array('required' => true, 'label' => 'Street'))
            ->add('street2', TextType::class, array('required' => false, 'label' => 'Street 2'))
            ->add('city', TextType::class, array('required' => true, 'label' => 'City'))
            ->add('state', 'oro_region', array('required' => false, 'label' => 'State'))
            ->add('state_text', HiddenType::class, array('required' => false, 'label' => 'Custom State'))
            ->add('postalCode', TextType::class, array('required' => true, 'label' => 'ZIP/Postal code'));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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
    public function getBlockPrefix()
    {
        return 'oro_address';
    }
}
