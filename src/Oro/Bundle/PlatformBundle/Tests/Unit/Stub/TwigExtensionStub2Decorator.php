<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Stub;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;

class TwigExtensionStub2Decorator extends AbstractExtension implements ServiceSubscriberInterface
{
    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            TokenStorageInterface::class,
            'router' => UrlGeneratorInterface::class
        ];
    }
}
