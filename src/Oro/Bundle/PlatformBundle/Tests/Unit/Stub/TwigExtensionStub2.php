<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Stub;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;

class TwigExtensionStub2 extends AbstractExtension implements ServiceSubscriberInterface
{
    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'request_stack' => RequestStack::class
        ];
    }
}
