<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ChannelTypeInterface;

class ChannelType extends AbstractType
{
    const NAME            = 'oro_integration_channel_form';
    const TYPE_FIELD_NAME = 'type';

    /** @var TypesRegistry */
    protected $registry;

    public function __construct(TypesRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            self::TYPE_FIELD_NAME,
            'choice',
            ['required' => true, 'choices' => $this->getAvailableTypesChoices(), 'label' => 'Type']
        );
        $builder->add('name', 'text', ['required' => true, 'label' => 'Name']);

        // add connectors
        $builder->add(
            'connectors',
            'choice',
            [
                'label'    => 'Connectors',
                'expanded' => true,
                'multiple' => true,
                'choices'  => [
                    'magento_customer_connector' => 'Customer connector'
                ]
            ]
        );

        // add transport type selector
        $builder->add(
            'transportType',
            'choice',
            [
                'label'    => 'Transport type',
                'choices'  => [
                    'magento_soap' => 'SOAP API v2'
                ],
                'mapped'   => false
            ]
        );
        $builder->add('transport', 'orocrm_magento_soap_transport_setting_form_type');
    }

    /**
     * Collect available types
     *
     * @return array
     */
    protected function getAvailableTypesChoices()
    {
        $registry = $this->registry;
        $types    = $registry->getRegisteredChannelTypes();
        $types    = $types->partition(
            function ($key, ChannelTypeInterface $type) use ($registry) {
                return !($registry->getRegisteredConnectorsTypes($key)->isEmpty()
                    || $registry->getRegisteredTransportTypes($key)->isEmpty());
            }
        );

        /** @var ArrayCollection $types */
        $types  = $types[0];
        $keys   = $types->getKeys();
        $values = $types->map(
            function (ChannelTypeInterface $type) {
                return $type->getLabel();
            }
        )->toArray();

        return array_combine($keys, $values);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\\Bundle\\IntegrationBundle\\Entity\\Channel',
                'intention'  => 'channel',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
