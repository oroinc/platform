<?php

namespace Oro\Bundle\AddressBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;
use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;

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
            ->add('label', 'text', array(
                'required' => false,
                'label' => 'oro.address.label.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('namePrefix', 'text', array(
                'required' => false,
                'label' => 'oro.address.name_prefix.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('firstName', 'text', array(
                'required' => false,
                'label' => 'oro.address.first_name.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('middleName', 'text', array(
                'required' => false,
                'label' => 'oro.address.middle_name.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('lastName', 'text', array(
                'required' => false,
                'label' => 'oro.address.last_name.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('nameSuffix', 'text', array(
                'required' => false,
                'label' => 'oro.address.name_suffix.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('organization', 'text', array(
                'required' => false,
                'label' => 'oro.address.organization.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('country', 'oro_country', array('required' => true, 'label' => 'oro.address.country.label'))
            ->add('street', 'text', array(
                'required' => false,
                'label' => 'oro.address.street.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('street2', 'text', array(
                'required' => false,
                'label' => 'oro.address.street2.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('city', 'text', array(
                'required' => false,
                'label' => 'oro.address.city.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('region', 'oro_region', array('required' => false, 'label' => 'oro.address.region.label'))
            ->add(
                'region_text',
                'hidden',
                array('required' => false, 'random_id' => true, 'label' => 'oro.address.region_text.label')
            )
            ->add('postalCode', 'text', array(
                'required' => false,
                'label' => 'oro.address.postal_code.label',
                StripTagsExtension::OPTION_NAME => true,
            ));
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
