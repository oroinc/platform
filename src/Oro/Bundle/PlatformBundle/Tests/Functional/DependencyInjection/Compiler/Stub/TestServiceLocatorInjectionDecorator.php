<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional\DependencyInjection\Compiler\Stub;

use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

class TestServiceLocatorInjectionDecorator extends TestServiceLocatorInjection
{
    /** @var TestServiceLocatorInjectionInterface */
    private $innerService;

    public function __construct(ContainerInterface $container, TestServiceLocatorInjectionInterface $innerService)
    {
        parent::__construct($container);
        $this->innerService = $innerService;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'router' => RouterInterface::class
        ]);
    }
}
