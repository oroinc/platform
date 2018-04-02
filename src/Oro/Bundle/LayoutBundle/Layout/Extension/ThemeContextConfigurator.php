<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;

class ThemeContextConfigurator implements ContextConfiguratorInterface
{
    /** @var RequestStack */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
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
