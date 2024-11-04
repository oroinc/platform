<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Request;

use Oro\Bundle\SecurityBundle\Request\SessionStorageOptionsManipulator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

final class SessionStorageOptionsManipulatorTest extends TestCase
{
    private Container $container;

    private SessionStorageOptionsManipulator $manipulator;

    protected function setUp(): void
    {
        $this->container = new ContainerStub([
            'oro_security.session.storage.options' => ['cookie_lifetime' => 7200],
            'session.storage.options' => ['cookie_lifetime' => 3600],
        ]);

        $this->manipulator = new SessionStorageOptionsManipulator($this->container);
    }

    public function testGetOriginalSessionOptions(): void
    {
        $this->assertSame(['cookie_lifetime' => 7200], $this->manipulator->getOriginalSessionOptions());
    }

    public function testGetSessionOptions(): void
    {
        $this->assertSame(['cookie_lifetime' => 3600], $this->manipulator->getSessionOptions());
    }

    public function testSetSessionOptions(): void
    {
        $newOptions = ['cookie_lifetime' => 14400];
        $this->manipulator->setSessionOptions($newOptions);

        $this->assertSame($newOptions, $this->container->getParameter('session.storage.options'));
    }
}
