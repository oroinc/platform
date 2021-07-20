<?php

namespace Oro\Bundle\ReminderBundle\Form\Type;

use Oro\Bundle\ReminderBundle\Model\SendProcessorRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type to select reminder send method.
 */
class MethodType extends AbstractType
{
    /** @var SendProcessorRegistry */
    private $sendProcessorRegistry;

    public function __construct(SendProcessorRegistry $sendProcessorRegistry)
    {
        $this->sendProcessorRegistry = $sendProcessorRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices'  => $this->getChoices(),
            'expanded' => false,
            'multiple' => false
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
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
        return 'oro_reminder_method';
    }

    /**
     * @return array [label => method, ...]
     */
    private function getChoices(): array
    {
        $result = [];
        foreach ($this->sendProcessorRegistry->getProcessors() as $method => $processor) {
            $result[$processor->getLabel()] = $method;
        }

        return $result;
    }
}
