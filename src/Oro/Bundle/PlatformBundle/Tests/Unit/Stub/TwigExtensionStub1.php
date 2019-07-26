<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Stub;

use Symfony\Component\DependencyInjection\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;

class TwigExtensionStub1 extends AbstractExtension implements ServiceSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'translator' => TranslatorInterface::class
        ];
    }
}
