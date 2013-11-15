<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\IntegrationBundle\Manager\ChannelTypeManager;
use Oro\Bundle\IntegrationBundle\Provider\ChannelTypeInterface;

class ChannelType extends AbstractType
{
    const NAME = 'oro_integration_channel_form';

    /** @var ChannelTypeManager */
    protected $cmt;

    /**
     * @param ChannelTypeManager $cmt
     */
    public function __construct(ChannelTypeManager $cmt)
    {
        $this->cmt = $cmt;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $keys    = $this->cmt->getRegisteredTypes()->getKeys();
        $values  = $this->cmt->getRegisteredTypes()->map(
            function (ChannelTypeInterface $type) {
                return $type->getLabel();
            }
        )->toArray();
        $choices = array_combine($keys, $values);
        $builder->add('type', 'choice', ['required' => true, 'choices' => $choices]);
        $builder->add('name', 'text', ['required' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\Bundle\IntegrationBundle\Entity\Channel',
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
