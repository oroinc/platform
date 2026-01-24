<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;

/**
 * Configures the layout context with the current theme identifier.
 *
 * This configurator registers the `theme` context variable, which identifies the
 * active theme for layout rendering. If not explicitly provided, it attempts to
 * resolve the theme from the current request's `_theme` attribute, defaulting to
 * `default` if no theme is specified.
 */
class ThemeContextConfigurator implements ContextConfiguratorInterface
{
    /** @var RequestStack */
    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    #[\Override]
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setDefaults(
                [
                    'theme' => function (Options $options, $value) {
                        $request = $this->requestStack->getCurrentRequest();
                        if (null === $value && $request) {
                            $value = $request->attributes->get('_theme', 'default');
                        }

                        return $value;
                    }
                ]
            )
            ->setAllowedTypes('theme', ['string', 'null']);
    }
}
