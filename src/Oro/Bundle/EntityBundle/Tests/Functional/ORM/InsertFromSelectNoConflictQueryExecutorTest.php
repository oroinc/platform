<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\ORM;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectNoConflictQueryExecutor;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class InsertFromSelectNoConflictQueryExecutorTest extends WebTestCase
{
    private InsertFromSelectNoConflictQueryExecutor $queryExecutor;

    protected function setUp(): void
    {
        $this->initClient();
        $this->queryExecutor = $this
            ->getContainer()
            ->get('oro_entity.orm.insert_from_select_no_conflict_query_executor');
    }

    private function getRepository(string $entityClass): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository($entityClass);
    }

    public function testInsert(): void
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
        self::assertEquals(1, $affectedRecords);

        /** @var User[] $users */
        $users = $this->getRepository(User::class)
            ->findAll();

        /** @var Item[] $items */
        $items = $this->getRepository(Item::class)
            ->findAll();

        self::assertCount(count($users), $items);

        foreach ($users as $index => $user) {
            $item = $items[$index];
            self::assertEquals($user->getEmail(), $item->stringValue);
            self::assertEquals($user->getId(), $item->integerValue);
            self::assertEquals($decimalValue, $item->decimalValue);
            self::assertTrue($item->booleanValue);
            self::assertEquals($user->getCreatedAt(), $item->datetimeValue);
            self::assertSame($user, $item->owner);
            self::assertSame($user->getOrganization(), $item->organization);
        }
    }

    public function testInsertNoConflict(): void
    {
        $decimalValue = 12345678.29;

        $group = $this->getRepository(Group::class)
            ->findOneBy(['name' => 'Administrators']);

        $queryBuilder = $this->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('u.id')
            ->addSelect('u.email')
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
                'id',
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
        self::assertEquals(1, $affectedRecords);

        $queryBuilder = $this->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('u.id')
            ->addSelect('u.email')
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

        $this->queryExecutor->setOnConflictIgnoredFields(['id']);

        $affectedRecords = $this->queryExecutor->execute(
            Item::class,
            [
                'id',
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
        self::assertEquals(0, $affectedRecords);

        /** @var User[] $users */
        $users = $this->getRepository(User::class)
            ->findAll();

        /** @var Item[] $items */
        $items = $this->getRepository(Item::class)
            ->findAll();

        self::assertCount(count($users), $items);

        foreach ($users as $index => $user) {
            $item = $items[$index];
            self::assertEquals($user->getEmail(), $item->stringValue);
            self::assertEquals($user->getId(), $item->integerValue);
            self::assertEquals($decimalValue, $item->decimalValue);
            self::assertTrue($item->booleanValue);
            self::assertEquals($user->getCreatedAt(), $item->datetimeValue);
            self::assertSame($user, $item->owner);
            self::assertSame($user->getOrganization(), $item->organization);
        }
    }
}
