<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Stub;

use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;

class TwigExtensionStub3 extends AbstractExtension implements ServiceSubscriberInterface
{
    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            TranslatorInterface::class
        ];
    }
}
