<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider\Filter;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Provider\Filter\ChoiceTreeUserProvider;

class ChoiceTreeUserProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var ChoiceTreeUserProvider */
    private $choiceTreeUserProvider;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->choiceTreeUserProvider = new ChoiceTreeUserProvider(
            $this->doctrine,
            $this->aclHelper,
            $this->createMock(DQLNameFormatter::class)
        );
    }

    public function testGetList()
    {
        $query = $this->createMock(AbstractQuery::class);
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->onlyMethods(['getQuery'])
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $query->expects($this->any())
            ->method('getArrayResult')
            ->willReturn($this->getExpectedData());
        $this->aclHelper->expects($this->any())
            ->method('apply')
            ->willReturn($query);

        $result = $this->choiceTreeUserProvider->getList();
        $this->assertSame($this->getExpectedData(), $result);
    }

    public function testGetEmptyList()
    {
        $query = $this->createMock(AbstractQuery::class);
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->onlyMethods(['getQuery'])
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $query->expects($this->any())
            ->method('getArrayResult')
            ->willReturn([]);
        $this->aclHelper->expects($this->any())
            ->method('apply')
            ->willReturn($query);

        $result = $this->choiceTreeUserProvider->getList();
        $this->assertEquals([], $result);
    }

    private function getExpectedData(): array
    {
        return [
            ['id' => 1, 'name' => 'user 1'],
            ['id' => '2', 'name' => 'user 2']
        ];
    }
}
