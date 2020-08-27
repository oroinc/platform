<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Container;

use Oro\Bundle\MigrationBundle\Container\MigrationContainer;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\DependencyInjection\Container as DependencyInjectionContainer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MigrationContainerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ParameterBagInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $parameterBag;

    /** @var DependencyInjectionContainer|\PHPUnit\Framework\MockObject\MockObject */
    private $publicContainer;

    /** @var PsrContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $privateContainer;

    /** @var MigrationContainer */
    private $migrationContainer;

    protected function setUp(): void
    {
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->publicContainer = $this->createMock(DependencyInjectionContainer::class);
        $this->privateContainer = $this->createMock(PsrContainerInterface::class);

        $this->migrationContainer = new MigrationContainer(
            $this->parameterBag,
            $this->publicContainer,
            $this->privateContainer
        );
    }

    public function testCompile(): void
    {
        $this->parameterBag->expects($this->never())
            ->method($this->anything());

        $this->publicContainer->expects($this->once())
            ->method('compile');

        $this->privateContainer->expects($this->never())
            ->method($this->anything());

        $this->migrationContainer->compile();
    }

    public function testIsCompiled(): void
    {
        $this->parameterBag->expects($this->never())
            ->method($this->anything());

        $this->publicContainer->expects($this->once())
            ->method('isCompiled')
            ->willReturn(true);

        $this->privateContainer->expects($this->never())
            ->method($this->anything());

        $this->assertTrue($this->migrationContainer->isCompiled());
    }

    public function testSet(): void
    {
        $id = 'test';
        $service = new \stdClass();

        $this->parameterBag->expects($this->never())
            ->method($this->anything());

        $this->publicContainer->expects($this->once())
            ->method('set')
            ->with($id, $service);

        $this->privateContainer->expects($this->never())
            ->method($this->anything());

        $this->migrationContainer->set($id, $service);
    }

    public function testHasInPublicContainer(): void
    {
        $id = 'test';

        $this->parameterBag->expects($this->never())
            ->method($this->anything());

        $this->publicContainer->expects($this->once())
            ->method('has')
            ->with($id)
            ->willReturn(true);

        $this->privateContainer->expects($this->never())
            ->method($this->anything());

        $this->assertTrue($this->migrationContainer->has($id));
    }

    public function testHasInPrivateContainer(): void
    {
        $id = 'test';

        $this->parameterBag->expects($this->never())
            ->method($this->anything());

        $this->publicContainer->expects($this->once())
            ->method('has')
            ->with($id)
            ->willReturn(false);

        $this->privateContainer->expects($this->once())
            ->method('has')
            ->with($id)
            ->willReturn(true);

        $this->assertTrue($this->migrationContainer->has($id));
    }

    public function testHas(): void
    {
        $id = 'test';

        $this->parameterBag->expects($this->never())
            ->method($this->anything());

        $this->publicContainer->expects($this->once())
            ->method('has')
            ->with($id)
            ->willReturn(false);

        $this->privateContainer->expects($this->once())
            ->method('has')
            ->with($id)
            ->willReturn(false);

        $this->assertFalse($this->migrationContainer->has($id));
    }

    public function testGetInPublicContainer(): void
    {
        $id = 'test';
        $service = new \stdClass();

        $this->parameterBag->expects($this->never())
            ->method($this->anything());

        $this->publicContainer->expects($this->once())
            ->method('get')
            ->with($id, DependencyInjectionContainer::IGNORE_ON_UNINITIALIZED_REFERENCE)
            ->willReturn($service);

        $this->privateContainer->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $this->assertSame(
            $service,
            $this->migrationContainer->get($id, DependencyInjectionContainer::IGNORE_ON_UNINITIALIZED_REFERENCE)
        );
    }

    public function testGetInPrivateContainer(): void
    {
        $id = 'test';
        $service = new \stdClass();

        $this->parameterBag->expects($this->never())
            ->method($this->anything());

        $this->publicContainer->expects($this->never())
            ->method($this->anything());

        $this->privateContainer->expects($this->once())
            ->method('has')
            ->willReturn(true);

        $this->privateContainer->expects($this->once())
            ->method('get')
            ->with($id)
            ->willReturn($service);

        $this->assertSame(
            $service,
            $this->migrationContainer->get($id, DependencyInjectionContainer::IGNORE_ON_UNINITIALIZED_REFERENCE)
        );
    }

    public function testInitialized(): void
    {
        $id = 'test';

        $this->parameterBag->expects($this->never())
            ->method($this->anything());

        $this->publicContainer->expects($this->once())
            ->method('initialized')
            ->with($id)
            ->willReturn(true);

        $this->privateContainer->expects($this->never())
            ->method($this->anything());

        $this->assertTrue($this->migrationContainer->initialized($id));
    }

    public function testReset(): void
    {
        $this->parameterBag->expects($this->never())
            ->method($this->anything());

        $this->publicContainer->expects($this->once())
            ->method('reset');

        $this->privateContainer->expects($this->never())
            ->method($this->anything());

        $this->migrationContainer->reset();
    }

    public function testGetServiceIds(): void
    {
        $ids = ['id1', 'id2'];

        $this->parameterBag->expects($this->never())
            ->method($this->anything());

        $this->publicContainer->expects($this->once())
            ->method('getServiceIds')
            ->willReturn($ids);

        $this->privateContainer->expects($this->never())
            ->method($this->anything());

        $this->assertEquals($ids, $this->migrationContainer->getServiceIds());
    }

    public function testGetRemovedIds(): void
    {
        $ids = ['id1', 'id2'];

        $this->parameterBag->expects($this->never())
            ->method($this->anything());

        $this->publicContainer->expects($this->once())
            ->method('getRemovedIds')
            ->willReturn($ids);

        $this->privateContainer->expects($this->never())
            ->method($this->anything());

        $this->assertEquals($ids, $this->migrationContainer->getRemovedIds());
    }
}
