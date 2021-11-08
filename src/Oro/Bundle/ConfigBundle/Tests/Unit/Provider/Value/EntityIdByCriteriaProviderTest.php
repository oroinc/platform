<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Provider\Value;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Provider\Value\Entity\EntityIdByCriteriaProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class EntityIdByCriteriaProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var string */
    private $entityClass;

    /** @var array */
    private $defaultEntityCriteria;

    /** @var EntityIdByCriteriaProvider */
    private $provider;

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

    public function testGetValue()
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

    public function testGetValueNoEntity()
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
