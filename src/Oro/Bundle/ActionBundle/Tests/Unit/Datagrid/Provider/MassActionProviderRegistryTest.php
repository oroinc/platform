<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Datagrid\Provider;

use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderInterface;
use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderRegistry;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\TestCase;

class MassActionProviderRegistryTest extends TestCase
{
    public function testGetProvider(): void
    {
        $provider = $this->createMock(MassActionProviderInterface::class);

        $container = TestContainerBuilder::create()
            ->add('test_provider', $provider)
            ->getContainer($this);
        $registry = new MassActionProviderRegistry($container);

        $this->assertSame($provider, $registry->getProvider('test_provider'));
        $this->assertNull($registry->getProvider('another'));
    }
}
