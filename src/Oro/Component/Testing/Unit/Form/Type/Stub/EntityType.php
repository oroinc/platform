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
class EntityType extends AbstractType
{
    /** @var string */
    protected $name;

    /** @var array|null */
    protected $options;

    /** @var array|null */
    protected $choices;

    /**
     * @param array $choices
     * @param string $name
     * @param array $options
     */
    public function __construct(array $choices, $name = 'entity', array $options = null)
    {
        $this->name = $name;
        $this->options = $options;
        $this->choices = $choices;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($options) {
                $data = $event->getData();
                if (!empty($options['multiple']) && $data instanceof ArrayCollection) {
                    $event->setData($data->toArray());
                }
            }
        );
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
