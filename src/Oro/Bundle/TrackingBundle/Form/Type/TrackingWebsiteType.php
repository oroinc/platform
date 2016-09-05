<?php

namespace Oro\Bundle\TrackingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TrackingWebsiteType extends AbstractType
{
    const NAME = 'oro_tracking_website';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function __construct($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                'text',
                [
                    'label' => 'oro.tracking.trackingwebsite.name.label'
                ]
            )
            ->add(
                'identifier',
                'text',
                [
                    'label'   => 'oro.tracking.trackingwebsite.identifier.label',
                    'tooltip' => 'oro.tracking.form.tooltip.identifier',
                ]
            )
            ->add(
                'url',
                'text',
                [
                    'label' => 'oro.tracking.trackingwebsite.url.label'
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'intention'  => 'tracking_website',
            ]
        );
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
        return self::NAME;
    }
}
