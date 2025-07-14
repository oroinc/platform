<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Provider\Value;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Provider\Value\Entity\EntityIdByCriteriaProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityIdByCriteriaProviderTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private string $entityClass;
    private array $defaultEntityCriteria;
    private EntityIdByCriteriaProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityClass = 'OroBundle:Entity';
        $this->defaultEntityCriteria = [
            'name' => 'My Entity',
        ];

        $this->provider = new EntityIdByCriteriaProvider(
            $this->doctrineHelper,
            $this->entityClass,
            $this->defaultEntityCriteria
        );
    }

    public function testGetValue(): void
    {
        // any entity
        $entity = $this->createMock(Config::class);

        $repository = $this->createMock(EntityRepository::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with($this->entityClass)
            ->willReturn($repository);

        $repository->expects(self::once())
            ->method('findOneBy')
            ->with($this->defaultEntityCriteria)
            ->willReturn($entity);

        $id = 2;

        $entity->expects(self::once())
            ->method('getId')
            ->willReturn($id);

        self::assertEquals($id, $this->provider->getValue());
    }

    public function testGetValueNoEntity(): void
    {
        $repository = $this->createMock(EntityRepository::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with($this->entityClass)
            ->willReturn($repository);

        $repository->expects(self::once())
            ->method('findOneBy')
            ->with($this->defaultEntityCriteria)
            ->willReturn(null);

        self::assertNull($this->provider->getValue());
    }
}
