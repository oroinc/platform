<?php

namespace Oro\Bundle\ReminderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\ReminderBundle\Model\SendProcessorRegistry;

class MethodType extends AbstractType
{
    /**
     * @var SendProcessorRegistry
     */
    protected $sendProcessorRegistry;

    /**
     * @param SendProcessorRegistry $sendProcessorRegistry
     */
    public function __construct(SendProcessorRegistry $sendProcessorRegistry)
    {
        $this->sendProcessorRegistry = $sendProcessorRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'choices' => $this->sendProcessorRegistry->getProcessorLabels(),
                'expanded' => false,
                'multiple' => false
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_reminder_method';
    }
}
