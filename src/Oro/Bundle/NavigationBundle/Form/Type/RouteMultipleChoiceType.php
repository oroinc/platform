<?php

declare(strict_types=1);

namespace Oro\Bundle\NavigationBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Multiple choice form type to choose routes from the route collection.
 * Similar to RouteChoiceType but allows selection of multiple values.
 */
class RouteMultipleChoiceType extends RouteChoiceType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'multiple' => true,
            'configs' => [
                'placeholder' => 'oro.navigation.route.form.placeholder_multiple',
                'allowClear' => true,
            ],
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_route_multiple_choice';
    }
}
