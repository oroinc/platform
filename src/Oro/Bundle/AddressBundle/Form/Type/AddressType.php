<?php

namespace Oro\Bundle\AddressBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;
use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for Address entity.
 * @see \Oro\Bundle\AddressBundle\Entity\Address
 */
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
            ->add('label', TextType::class, array(
                'required' => false,
                'label' => 'oro.address.label.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('namePrefix', TextType::class, array(
                'required' => false,
                'label' => 'oro.address.name_prefix.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('firstName', TextType::class, array(
                'required' => false,
                'label' => 'oro.address.first_name.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('middleName', TextType::class, array(
                'required' => false,
                'label' => 'oro.address.middle_name.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('lastName', TextType::class, array(
                'required' => false,
                'label' => 'oro.address.last_name.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('nameSuffix', TextType::class, array(
                'required' => false,
                'label' => 'oro.address.name_suffix.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('organization', TextType::class, array(
                'required' => false,
                'label' => 'oro.address.organization.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('country', CountryType::class, array('required' => true, 'label' => 'oro.address.country.label'))
            ->add('street', TextType::class, array(
                'required' => false,
                'label' => 'oro.address.street.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('street2', TextType::class, array(
                'required' => false,
                'label' => 'oro.address.street2.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('city', TextType::class, array(
                'required' => false,
                'label' => 'oro.address.city.label',
                StripTagsExtension::OPTION_NAME => true,
            ))
            ->add('region', RegionType::class, array('required' => false, 'label' => 'oro.address.region.label'))
            ->add(
                'region_text',
                HiddenType::class,
                array('required' => false, 'random_id' => true, 'label' => 'oro.address.region_text.label')
            )
            ->add('postalCode', TextType::class, array(
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
                'csrf_token_id' => 'address',
                'single_form' => true,
                'region_route' => 'oro_api_country_get_regions'
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
