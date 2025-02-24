<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RouteChoiceTypeStub extends AbstractType
{
    /**
     * @var array
     */
    protected $choices;

    public function __construct(array $choices)
    {
        $this->choices = $choices;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(
            [
                'path_filter',
                'name_filter',
                'options_filter',
                'without_parameters_only',
                'add_titles',
                'with_titles_only',
            ]
        );

        $resolver->setDefault('choices', $this->choices);
        $resolver->setDefault('menu_name', 'menu');
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_route_choice';
    }
}
