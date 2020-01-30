<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional\DependencyInjection\Compiler\Stub;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

class TestServiceLocatorInjectedViaSetter extends TestServiceLocatorInjection
{
    /** @var ContainerInterface */
    private $containerInjectedViaConstructor;

    /**
     * @param ContainerInterface|null $container
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->containerInjectedViaConstructor = $container;
    }

    /**
     * @return ContainerInterface|null
     */
    public function getContainerInjectedViaConstructor(): ?ContainerInterface
    {
        return $this->containerInjectedViaConstructor;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            RouterInterface::class,
            LoggerInterface::class
        ]);
    }
}
