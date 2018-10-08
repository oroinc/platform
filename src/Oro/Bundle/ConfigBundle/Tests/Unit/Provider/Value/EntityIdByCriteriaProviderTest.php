<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Provider\Value\Entity;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Provider\Value\Entity\EntityIdByCriteriaProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class EntityIdByCriteriaProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var array
     */
    private $defaultEntityCriteria;

    /**
     * @var EntityIdByCriteriaProvider
     */
    private $provider;

    protected function setUp()
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

        $this->doctrineHelper->expects(static::once())
            ->method('getEntityRepositoryForClass')
            ->with($this->entityClass)
            ->willReturn($repository);

        $repository->expects(static::once())
            ->method('findOneBy')
            ->with($this->defaultEntityCriteria)
            ->willReturn($entity);

        $id = 2;

        $entity->expects(static::once())
            ->method('getId')
            ->willReturn($id);

        static::assertEquals($id, $this->provider->getValue());
    }

    public function testGetValueNoEntity()
    {
        $repository = $this->createMock(EntityRepository::class);

        $this->doctrineHelper->expects(static::once())
            ->method('getEntityRepositoryForClass')
            ->with($this->entityClass)
            ->willReturn($repository);

        $repository->expects(static::once())
            ->method('findOneBy')
            ->with($this->defaultEntityCriteria)
            ->willReturn(null);

        static::assertNull($this->provider->getValue());
    }
}
