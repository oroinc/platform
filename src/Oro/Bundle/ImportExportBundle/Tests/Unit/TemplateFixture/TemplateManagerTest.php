<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\TemplateFixture;

use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\TemplateFixture\EmptyFixture;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRegistry;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRepositoryInterface;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Fixtures\TestTemplateEntityRepository;

class TemplateManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TemplateEntityRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRegistry;

    /** @var TemplateManager */
    private $templateManager;

    protected function setUp(): void
    {
        $this->entityRegistry = $this->createMock(TemplateEntityRegistry::class);

        $this->templateManager = new TemplateManager($this->entityRegistry);
    }

    public function testHasEntityRepository()
    {
        $repository = $this->createMock(TemplateEntityRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getEntityClass')
            ->willReturn('Test\Entity');

        $this->templateManager->addEntityRepository($repository);

        $this->assertFalse(
            $this->templateManager->hasEntityRepository('unknown')
        );
        $this->assertTrue(
            $this->templateManager->hasEntityRepository('Test\Entity')
        );
    }

    public function testHasEntityFixture()
    {
        $repository = $this->createMock(TemplateEntityRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getEntityClass')
            ->willReturn('Test\Entity1');

        $fixture = $this->createMock(TemplateFixtureInterface::class);
        $fixture->expects($this->once())
            ->method('getEntityClass')
            ->willReturn('Test\Entity2');

        $this->templateManager->addEntityRepository($repository);
        $this->templateManager->addEntityRepository($fixture);

        $this->assertFalse(
            $this->templateManager->hasEntityFixture('unknown')
        );
        $this->assertFalse(
            $this->templateManager->hasEntityFixture('Test\Entity1')
        );
        $this->assertTrue(
            $this->templateManager->hasEntityFixture('Test\Entity2')
        );
    }

    public function testGetEntityRepository()
    {
        $repository = $this->createMock(TemplateEntityRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getEntityClass')
            ->willReturn('Test\Entity1');

        $fixture = $this->createMock(TemplateFixtureInterface::class);
        $fixture->expects($this->once())
            ->method('getEntityClass')
            ->willReturn('Test\Entity2');

        $this->templateManager->addEntityRepository($repository);
        $this->templateManager->addEntityRepository($fixture);

        $this->assertSame(
            $repository,
            $this->templateManager->getEntityRepository('Test\Entity1')
        );

        $this->assertSame(
            $fixture,
            $this->templateManager->getEntityRepository('Test\Entity2')
        );
    }

    public function testGetEntityRepositoryForUnknownEntityType()
    {
        $repository = $this->templateManager->getEntityFixture('Test\Entity1');

        $this->assertInstanceOf(
            EmptyFixture::class,
            $repository
        );
        $this->assertEquals('Test\Entity1', $repository->getEntityClass());
    }

    public function testGetEntityFixture()
    {
        $repository = $this->createMock(TemplateEntityRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getEntityClass')
            ->willReturn('Test\Entity1');

        $fixture = $this->createMock(TemplateFixtureInterface::class);
        $fixture->expects($this->once())
            ->method('getEntityClass')
            ->willReturn('Test\Entity2');

        $this->templateManager->addEntityRepository($repository);
        $this->templateManager->addEntityRepository($fixture);

        $this->assertSame(
            $fixture,
            $this->templateManager->getEntityFixture('Test\Entity2')
        );
    }

    public function testGetEntityFixtureForUnknownEntityType()
    {
        $fixture = $this->templateManager->getEntityFixture('Test\Entity1');

        $this->assertInstanceOf(
            EmptyFixture::class,
            $fixture
        );
        $this->assertEquals('Test\Entity1', $fixture->getEntityClass());
    }

    public function testFrozenTemplateManager()
    {
        $repository = $this->createMock(TemplateEntityRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getEntityClass')
            ->willReturn('Test\Entity1');

        $this->templateManager->addEntityRepository($repository);

        $this->assertSame(
            $repository,
            $this->templateManager->getEntityRepository('Test\Entity1')
        );

        $anotherRepository = $this->createMock(TemplateEntityRepositoryInterface::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf('The repository "%s" cannot be added to the frozen registry.', get_class($anotherRepository))
        );
        $this->templateManager->addEntityRepository($anotherRepository);
    }

    public function testInitialization()
    {
        $repository = new TestTemplateEntityRepository();
        $this->templateManager->addEntityRepository($repository);

        $this->assertSame(
            $this->templateManager,
            $this->templateManager->getEntityRepository($repository->getEntityClass())->getTemplateManager()
        );
    }
}
