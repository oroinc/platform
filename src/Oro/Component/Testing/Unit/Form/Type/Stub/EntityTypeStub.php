<?php

namespace Oro\Component\Testing\Unit\Form\Type\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Stub for entity form type.
 */
class EntityTypeStub extends AbstractType
{
    private ?array $choices;
    private ?array $options;

    public function __construct(array $choices = [], array $options = null)
    {
        $this->choices = $choices;
        $this->options = $options;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $defaultOptions = [
            'choice_loader' => new ChoiceLoaderStub($this->choices),
            'choice_label' => null,
            'query_builder' => null,
            'create_enabled' => false,
            'class' => null,
            'acl_options' => []
        ];

        if ($this->options) {
            $defaultOptions = array_merge($defaultOptions, $this->options);
        }

        $resolver->setDefaults($defaultOptions);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $data = $event->getData();
            if (!empty($options['multiple']) && $data instanceof ArrayCollection) {
                $event->setData($data->toArray());
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
