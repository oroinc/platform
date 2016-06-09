<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

class RootIdContextConfigurator implements ContextConfiguratorInterface
{
    /**
     * @var RequestStack
     */
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
        $request = $this->requestStack->getCurrentRequest();

        $context->getResolver()
            ->setDefaults(
                [
                    'root_id' => function (Options $options, $value) use ($request) {
                        if (null === $value && $request) {
                            $value = $request->get('layout_root_id');
                        }

                        return $value;
                    }
                ]
            )
            ->setAllowedTypes([
                'root_id' => ['string', 'null'],
            ]);
    }
}
