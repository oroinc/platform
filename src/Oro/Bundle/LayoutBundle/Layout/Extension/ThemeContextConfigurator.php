<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Options;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

class ThemeContextConfigurator implements ContextConfiguratorInterface
{
    /** @var Request|null */
    protected $request;

    /**
     * Synchronized DI method call, sets current request for further usage
     *
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
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
                        if (null === $value && $this->request) {
                            if (null === $value) {
                                $value = $this->request->attributes->get('_theme');
                            }
                        }

                        return $value;
                    }
                ]
            )
            ->setAllowedTypes(['theme' => ['string', 'null']]);
    }
}
