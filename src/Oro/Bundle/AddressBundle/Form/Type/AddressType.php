<?php

namespace Oro\Bundle\AddressBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraint;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;

class AddressType extends AbstractType
{
    const ABSTRACT_ADDRESS_GROUP = 'AbstractAddress';
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
            ->add('label', 'text', array('required' => false, 'label' => 'oro.address.label.label'))
            ->add('namePrefix', 'text', array('required' => false, 'label' => 'oro.address.name_prefix.label'))
            ->add('firstName', 'text', array('required' => false, 'label' => 'oro.address.first_name.label'))
            ->add('middleName', 'text', array('required' => false, 'label' => 'oro.address.middle_name.label'))
            ->add('lastName', 'text', array('required' => false, 'label' => 'oro.address.last_name.label'))
            ->add('nameSuffix', 'text', array('required' => false, 'label' => 'oro.address.name_suffix.label'))
            ->add('organization', 'text', array('required' => false, 'label' => 'oro.address.organization.label'))
            ->add('country', 'oro_country', array('required' => true, 'label' => 'oro.address.country.label'))
            ->add('street', 'text', array('required' => true, 'label' => 'oro.address.street.label'))
            ->add('street2', 'text', array('required' => false, 'label' => 'oro.address.street2.label'))
            ->add('city', 'text', array('required' => true, 'label' => 'oro.address.city.label'))
            ->add('region', 'oro_region', array('required' => false, 'label' => 'oro.address.region.label'))
            ->add(
                'region_text',
                'hidden',
                array('required' => false, 'random_id' => true, 'label' => 'oro.address.region_text.label')
            )
            ->add('postalCode', 'text', array('required' => true, 'label' => 'oro.address.postal_code.label'));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\Bundle\AddressBundle\Entity\Address',
                'intention' => 'address',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                'single_form' => true,
                'region_route' => 'oro_api_country_get_regions',
                'validation_groups' => [Constraint::DEFAULT_GROUP, self::ABSTRACT_ADDRESS_GROUP],
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!empty($options['region_route'])) {
            $view->vars['region_route'] = $options['region_route'];
        }
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
        return 'oro_address';
    }
}
