<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider\Filter;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Provider\Filter\ChoiceTreeUserProvider;

class ChoiceTreeUserProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var Registry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var ChoiceTreeUserProvider */
    private $choiceTreeUserProvider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(Registry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->choiceTreeUserProvider = new ChoiceTreeUserProvider(
            $this->registry,
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

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManager')
            ->willReturn($manager);

        $query->expects($this->any())
            ->method('getArrayResult')
            ->willReturn($this->getExpectedData());
        $this->aclHelper->expects($this->any())
            ->method('apply')
            ->willReturn($query);

        $result = $this->choiceTreeUserProvider->getList();
        $this->assertEquals($this->getExpectedData(), $result);
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

        $manager = $this->createMock(ObjectManager::class);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $manager->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $this->registry->expects($this->once())
            ->method('getManager')
            ->willReturn($manager);

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
            [
                'name' => 'user 1',
                'id' => 1,
            ],
            [
                'name' => 'user 2',
                'id' => '2',
            ]
        ];
    }
}
