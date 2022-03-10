<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\ApiBundle\ApiDoc\EntityDescriptionProvider;
use Oro\Bundle\ApiBundle\ApiDoc\EntityNameProvider;

class EntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityDescriptionProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityDescriptionProvider;

    /** @var EntityNameProvider */
    private $entityNameProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityDescriptionProvider = $this->createMock(EntityDescriptionProvider::class);

        $this->entityNameProvider = new EntityNameProvider(
            $this->entityDescriptionProvider,
            (new InflectorFactory())->build()
        );
    }

    public function testGetEntityName(): void
    {
        $entityClass = 'Acme\TestEntity';

        $this->entityDescriptionProvider->expects(self::exactly(2))
            ->method('getEntityDescription')
            ->with($entityClass)
            ->willReturn('Test Description');

        self::assertEquals('Test Description', $this->entityNameProvider->getEntityName($entityClass));
        self::assertEquals('test description', $this->entityNameProvider->getEntityName($entityClass, true));
    }

    public function testGetEntityNameWhenNoEntityDescription(): void
    {
        $entityClass = 'Acme\TestEntity';

        $this->entityDescriptionProvider->expects(self::exactly(2))
            ->method('getEntityDescription')
            ->with($entityClass)
            ->willReturn(null);

        self::assertEquals('Test Entity', $this->entityNameProvider->getEntityName($entityClass));
        self::assertEquals('test entity', $this->entityNameProvider->getEntityName($entityClass, true));
    }

    public function testGetEntityPluralName(): void
    {
        $entityClass = 'Acme\TestEntity';

        $this->entityDescriptionProvider->expects(self::exactly(2))
            ->method('getEntityPluralDescription')
            ->with($entityClass)
            ->willReturn('Test Description');

        self::assertEquals('Test Description', $this->entityNameProvider->getEntityPluralName($entityClass));
        self::assertEquals('test description', $this->entityNameProvider->getEntityPluralName($entityClass, true));
    }

    public function testGetEntityPluralNameWhenNoEntityPluralDescription(): void
    {
        $entityClass = 'Acme\TestEntity';

        $this->entityDescriptionProvider->expects(self::exactly(2))
            ->method('getEntityPluralDescription')
            ->with($entityClass)
            ->willReturn(null);

        self::assertEquals('Test Entities', $this->entityNameProvider->getEntityPluralName($entityClass));
        self::assertEquals('test entities', $this->entityNameProvider->getEntityPluralName($entityClass, true));
    }
}
