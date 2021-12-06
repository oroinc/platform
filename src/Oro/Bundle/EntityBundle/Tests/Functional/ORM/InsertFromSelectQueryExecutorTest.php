<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\ORM;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;

class InsertFromSelectQueryExecutorTest extends WebTestCase
{
    /** @var InsertFromSelectQueryExecutor */
    private $queryExecutor;

    protected function setUp(): void
    {
        $this->initClient();
        $this->queryExecutor = $this->getContainer()->get('oro_entity.orm.insert_from_select_query_executor');
    }

    private function getRepository(string $entityClass): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository($entityClass);
    }

    public function testInsert()
    {
        $decimalValue = 12345678.29;

        $group = $this->getRepository(Group::class)
            ->findOneBy(['name' => 'Administrators']);

        $queryBuilder = $this->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('u.email')
            ->addSelect('u.id')
            ->addSelect((string)$decimalValue)
            ->addSelect('(TRUE)')
            ->addSelect('u.createdAt')
            ->addSelect('u.id')
            ->addSelect('IDENTITY(u.organization)')
            ->innerJoin('u.groups', 'g')
            ->where('u.createdAt <= :datetime')
            ->andWhere('g = :group')
            ->setParameter('datetime', new \DateTime(), Types::DATETIME_MUTABLE)
            ->setParameter('group', $group);

        $affectedRecords = $this->queryExecutor->execute(
            Item::class,
            [
                'stringValue',
                'integerValue',
                'decimalValue',
                'booleanValue',
                'datetimeValue',
                'owner',
                'organization',
            ],
            $queryBuilder
        );
        $this->assertEquals(1, $affectedRecords);

        /** @var User[] $users */
        $users = $this->getRepository(User::class)
            ->findAll();

        /** @var Item[] $items */
        $items = $this->getRepository(Item::class)
            ->findAll();

        $this->assertCount(count($users), $items);

        foreach ($users as $index => $user) {
            $item = $items[$index];
            $this->assertEquals($user->getEmail(), $item->stringValue);
            $this->assertEquals($user->getId(), $item->integerValue);
            $this->assertEquals($decimalValue, $item->decimalValue);
            $this->assertTrue($item->booleanValue);
            $this->assertEquals($user->getCreatedAt(), $item->datetimeValue);
            $this->assertSame($user, $item->owner);
            $this->assertSame($user->getOrganization(), $item->organization);
        }
    }
}
