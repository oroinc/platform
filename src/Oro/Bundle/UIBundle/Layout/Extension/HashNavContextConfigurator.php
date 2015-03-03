<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

use Symfony\Component\HttpFoundation\Request;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;

use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Symfony\Component\OptionsResolver\Options;

class HashNavContextConfigurator implements ContextConfiguratorInterface
{
    const HASH_NAVIGATION_HEADER = ResponseHashnavListener::HASH_NAVIGATION_HEADER;

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
        $context->getDataResolver()
            ->setOptional(['hash_navigation'])
            ->setAllowedTypes(['hash_navigation' => 'bool'])
            ->setNormalizers(
                [
                    'hash_navigation' => function (Options $options, $hashNavigation) {
                        if ($hashNavigation === null) {
                            $hashNavigation =
                                $this->request
                                && (
                                    $this->request->headers->get(self::HASH_NAVIGATION_HEADER) == true
                                    || $this->request->get(self::HASH_NAVIGATION_HEADER) == true
                                );
                        }

                        return $hashNavigation;
                    }
                ]
            );
    }
}
