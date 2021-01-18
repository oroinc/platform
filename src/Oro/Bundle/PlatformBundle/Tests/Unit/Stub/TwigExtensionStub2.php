<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Stub;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;

class TwigExtensionStub2 extends AbstractExtension implements ServiceSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'request_stack' => RequestStack::class
        ];
    }
}
