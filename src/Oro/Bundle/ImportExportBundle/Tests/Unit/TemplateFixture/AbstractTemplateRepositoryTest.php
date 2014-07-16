<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRegistry;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;

class AbstractTemplateRepositoryTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'Test\Entity';

    /** @var TemplateEntityRegistry */
    protected $entityRegistry;

    /** @var TemplateManager */
    protected $templateManager;

    /** @var AbstractTemplateRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $templateRepository;

    protected function setUp()
    {
        $this->entityRegistry  = new TemplateEntityRegistry();
        $this->templateManager = new TemplateManager($this->entityRegistry);

        $this->templateRepository = $this->getMockForAbstractClass(
            'Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository',
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
            ->will($this->returnValue(self::ENTITY_CLASS));
    }

    public function testGetEntity()
    {
        $entityKey = 'test1';
        $entity    = new \stdClass();

        $this->templateRepository->expects($this->once())
            ->method('createEntity')
            ->with($entityKey)
            ->will($this->returnValue($entity));

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
        $entity    = new \stdClass();

        $repository = $this->getMock(
            'Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRepositoryInterface'
        );
        $repository->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue(self::ENTITY_CLASS));
        $this->templateManager->addEntityRepository($repository);

        $this->templateRepository->expects($this->once())
            ->method('createEntity')
            ->with($entityKey)
            ->will($this->returnValue($entity));
        $repository->expects($this->once())
            ->method('fillEntityData')
            ->with($entityKey, $this->identicalTo($entity));

        // test that new entity is created
        $data = $this->callProtectedMethod($this->templateRepository, 'getEntityData', [$entityKey]);
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
        $data = $this->callProtectedMethod($this->templateRepository, 'getEntityData', [$entityKey]);
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

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage Unknown entity: "stdClass"; key: "test1".
     */
    public function testFillEntityData()
    {
        $entityKey = 'test1';
        $entity    = new \stdClass();

        $this->templateRepository->fillEntityData($entityKey, $entity);
    }

    /**
     * @param mixed  $obj
     * @param string $methodName
     * @param array  $args
     *
     * @return mixed
     */
    public function callProtectedMethod($obj, $methodName, array $args)
    {
        $class  = new \ReflectionClass($obj);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }
}
