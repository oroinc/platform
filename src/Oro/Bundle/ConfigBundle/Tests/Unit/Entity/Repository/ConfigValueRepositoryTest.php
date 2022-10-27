<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Entity\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigValueRepository;

class ConfigValueRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigValueRepository */
    private $repository;

    /** @var EntityManager */
    private $om;

    protected function setUp(): void
    {
        $this->om = $this->createMock(EntityManager::class);

        $this->repository = new ConfigValueRepository(
            $this->om,
            new ClassMetadata('Oro\Bundle\ConfigBundle\Entity\Config\Value')
        );
    }

    /**
     * test removeValues
     */
    public function testRemoveValues()
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('execute');

        $queryBuilder->expects($this->once())
            ->method('delete')
            ->with(ConfigValue::class, 'cv')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->willReturnSelf();
        $queryBuilder->expects($this->exactly(3))
            ->method('setParameter')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->om->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->om->expects($this->once())
            ->method('beginTransaction');

        $this->om->expects($this->once())
            ->method('commit');

        $removed = [
            ['oro_user', 'level']
        ];

        $configMock = $this->createMock(Config::class);

        $this->repository->removeValues($configMock, $removed);
    }
}
