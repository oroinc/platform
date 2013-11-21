<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\IntegrationBundle\Provider\ConnectorTypeInterface;

abstract class AbstractConnectorSettingFormType extends AbstractType
{
    /** @var ConnectorTypeInterface */
    protected $connector;

    public function __construct(ConnectorTypeInterface $connector)
    {
        $this->connector = $connector;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $channel = $options['channel'];
        $builder->add(
            'transport',
            'entity',
            [
                'label'         => 'Transport type',
                'class'         => 'OroIntegrationBundle:Transport',
                'query_builder' => function (EntityRepository $repo) use ($channel) {
                    return $repo->createQueryBuilder('t')
                        ->where('t.channel = :channel')
                        ->setParameter('channel', $channel);
                },
                'property'      => 'label',
                'empty_value'   => 'Choose configured transport',
                'constraints'   => new NotBlank()
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['data_class' => $this->connector->getSettingsEntityFQCN()]);
        $resolver->setRequired(['channel']);
    }
}
