<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Form\EventListener\ChannelFormSubscriber as IntegrationFormSubscriber;
use Oro\Bundle\IntegrationBundle\Form\EventListener\DefaultUserOwnerSubscriber;
use Oro\Bundle\IntegrationBundle\Form\EventListener\OrganizationSubscriber;

class ChannelType extends AbstractType
{
    const NAME            = 'oro_integration_channel_form';
    const TYPE_FIELD_NAME = 'type';

    /** @var TypesRegistry */
    protected $registry;

    /** @var DefaultUserOwnerSubscriber */
    protected $defaultUserOwnerSubscriber;

    /** @var IntegrationFormSubscriber */
    protected $integrationFormSubscriber;

    /** @var IntegrationFormSubscriber */
    protected $organizationSubscriber;

    /**
     * @param TypesRegistry              $registry
     * @param DefaultUserOwnerSubscriber $defaultUserOwnerSubscriber
     * @param IntegrationFormSubscriber  $integrationFormSubscriber
     * @param OrganizationSubscriber     $organizationSubscriber
     */
    public function __construct(
        TypesRegistry $registry,
        DefaultUserOwnerSubscriber $defaultUserOwnerSubscriber,
        IntegrationFormSubscriber $integrationFormSubscriber,
        OrganizationSubscriber $organizationSubscriber
    ) {
        $this->registry                   = $registry;
        $this->defaultUserOwnerSubscriber = $defaultUserOwnerSubscriber;
        $this->integrationFormSubscriber  = $integrationFormSubscriber;
        $this->organizationSubscriber     = $organizationSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->integrationFormSubscriber);
        $builder->addEventSubscriber($this->defaultUserOwnerSubscriber);
        $builder->addEventSubscriber($this->organizationSubscriber);

        $builder->add(
            self::TYPE_FIELD_NAME,
            'oro_integration_type_select',
            [
                'required' => true,
                'choices'  => $this->registry->getAvailableIntegrationTypesDetailedChoiceList(),
                'label'    => 'oro.integration.integration.type.label'
            ]
        );
        $builder->add('name', 'text', ['required' => true, 'label' => 'oro.integration.integration.name.label']);

        // add transport type selector
        $builder->add(
            'transportType',
            'choice',
            [
                'label'       => 'oro.integration.integration.transport.label',
                'choices'     => [], //will be filled in event listener
                'mapped'      => false,
                'constraints' => new NotBlank()
            ]
        );

        // add connectors
        $builder->add(
            'connectors',
            'choice',
            [
                'label'    => 'oro.integration.integration.connectors.label',
                'expanded' => true,
                'multiple' => true,
                'choices'  => [], //will be filled in event listener
                'required' => false,
            ]
        );

        $builder->add(
            'defaultUserOwner',
            'oro_user_select',
            [
                'required' => true,
                'label'    => 'oro.integration.integration.default_user_owner.label',
                'tooltip'  => 'oro.integration.integration.default_user_owner.tooltip'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => 'Oro\\Bundle\\IntegrationBundle\\Entity\\Channel',
                'intention'          => 'channel',
                'cascade_validation' => true
            ]
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
