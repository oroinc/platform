<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Entity\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigValueRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigValueRepositoryTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ConfigValueRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->repository = new ConfigValueRepository(
            $this->em,
            new ClassMetadata('Oro\Bundle\ConfigBundle\Entity\Config\Value')
        );
    }

    /**
     * test removeValues
     */
    public function testRemoveValues(): void
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

        $this->em->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->em->expects($this->once())
            ->method('beginTransaction');

        $this->em->expects($this->once())
            ->method('commit');

        $removed = [
            ['oro_user', 'level']
        ];

        $configMock = $this->createMock(Config::class);

        $this->repository->removeValues($configMock, $removed);
    }
}
