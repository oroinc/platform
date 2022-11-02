<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\TemplateFixture;

use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRegistry;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRepositoryInterface;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;
use Oro\Component\Testing\ReflectionUtil;

class AbstractTemplateRepositoryTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS = 'Test\Entity';

    /** @var TemplateEntityRegistry */
    private $entityRegistry;

    /** @var TemplateManager */
    private $templateManager;

    /** @var AbstractTemplateRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $templateRepository;

    protected function setUp(): void
    {
        $this->entityRegistry = new TemplateEntityRegistry();
        $this->templateManager = new TemplateManager($this->entityRegistry);

        $this->templateRepository = $this->getMockForAbstractClass(
            AbstractTemplateRepository::class,
            [],
            '',
            true,
            true,
            true,
            ['getEntityClass', 'createEntity']
        );

        $this->templateRepository->setTemplateManager($this->templateManager);

        $this->templateRepository->expects($this->any())
            ->method('getEntityClass')
            ->willReturn(self::ENTITY_CLASS);
    }

    public function testGetEntity()
    {
        $entityKey = 'test1';
        $entity = new \stdClass();

        $this->templateRepository->expects($this->once())
            ->method('createEntity')
            ->with($entityKey)
            ->willReturn($entity);

        // test that new entity is created
        $this->assertSame(
            $entity,
            $this->templateRepository->getEntity($entityKey)
        );
        $this->assertSame(
            $entity,
            $this->entityRegistry->getEntity(self::ENTITY_CLASS, $entityKey)
        );

        // test that existing entity is returned
        $this->assertSame(
            $entity,
            $this->templateRepository->getEntity($entityKey)
        );
        $this->assertSame(
            $entity,
            $this->entityRegistry->getEntity(self::ENTITY_CLASS, $entityKey)
        );
    }

    public function testGetEntityData()
    {
        $entityKey = 'test1';
        $entity = new \stdClass();

        $repository = $this->createMock(TemplateEntityRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getEntityClass')
            ->willReturn(self::ENTITY_CLASS);
        $this->templateManager->addEntityRepository($repository);

        $this->templateRepository->expects($this->once())
            ->method('createEntity')
            ->with($entityKey)
            ->willReturn($entity);
        $repository->expects($this->once())
            ->method('fillEntityData')
            ->with($entityKey, $this->identicalTo($entity));

        // test that new entity is created
        $data = ReflectionUtil::callMethod($this->templateRepository, 'getEntityData', [$entityKey]);
        $data = iterator_to_array($data);
        $this->assertSame(
            $entity,
            current($data)
        );
        $this->assertSame(
            $entity,
            $this->entityRegistry->getEntity(self::ENTITY_CLASS, $entityKey)
        );

        // test that existing entity is returned
        $data = ReflectionUtil::callMethod($this->templateRepository, 'getEntityData', [$entityKey]);
        $data = iterator_to_array($data);
        $this->assertSame(
            $entity,
            current($data)
        );
        $this->assertSame(
            $entity,
            $this->entityRegistry->getEntity(self::ENTITY_CLASS, $entityKey)
        );
    }

    public function testFillEntityData()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unknown entity: "stdClass"; key: "test1".');

        $entityKey = 'test1';
        $entity = new \stdClass();

        $this->templateRepository->fillEntityData($entityKey, $entity);
    }
}
