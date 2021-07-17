<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\MultiInsertQueryExecutor;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class MultiInsertQueryExecutorTest extends WebTestCase
{
    private const BATCH_SIZE = 1;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var MultiInsertQueryExecutor
     */
    protected $queryExecutor;

    protected function setUp(): void
    {
        $this->initClient();
        $this->registry = $this->getContainer()->get('doctrine');
        $this->queryExecutor = $this->getContainer()->get('oro_entity.orm.multi_insert_query_executor');
        $this->queryExecutor->setBatchSize(self::BATCH_SIZE);
    }

    public function testInsert()
    {
        $decimalValue = 12345678.29;

        $group = $this->registry
            ->getManagerForClass('OroUserBundle:Group')
            ->getRepository('OroUserBundle:Group')
            ->findOneBy(['name' => 'Administrators']);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->registry
            ->getManagerForClass('OroUserBundle:User')
            ->getRepository('OroUserBundle:User')
            ->createQueryBuilder('u')
            ->select('u.email')
            ->addSelect('u.id')
            ->addSelect("$decimalValue")
            ->addSelect('(TRUE)')
            ->addSelect('u.createdAt')
            ->addSelect('u.id')
            ->addSelect('IDENTITY(u.organization)')
            ->innerJoin('u.groups', 'g')
            ->where('u.createdAt <= :datetime')
            ->andWhere('g = :group')
            ->setParameter('datetime', new \DateTime(), Types::DATETIME_MUTABLE)
            ->setParameter('group', $group)
        ;

        $affectedRecords = $this->queryExecutor->execute(
            'OroTestFrameworkBundle:Item',
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
        $users = $this->registry
            ->getManagerForClass(User::class)
            ->getRepository(User::class)
            ->findAll();

        /** @var Item[] $items */
        $items = $this->registry->getManagerForClass(Item::class)
            ->getRepository(Item::class)
            ->findAll();

        $this->assertNotEmpty($items);
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

    public function testInsertMultipleBatches()
    {
        $decimalValue = 12345678.29;
        $multiInsertRole = new Role();
        $multiInsertRole->setLabel('statusMultiInsertRole');
        $this->registry->getManagerForClass(Role::class)->persist($multiInsertRole);

        $expectedUserCount = self::BATCH_SIZE + 1;
        $organization = $this->registry->getRepository('OroOrganizationBundle:Organization')->getFirst();
        $this->createUsers($multiInsertRole, $organization, $expectedUserCount);
        $this->registry->getManagerForClass(Role::class)->refresh($multiInsertRole);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->registry
            ->getManagerForClass('OroUserBundle:User')
            ->getRepository('OroUserBundle:User')
            ->createQueryBuilder('u')
            ->select('u.email')
            ->addSelect('u.id')
            ->addSelect("$decimalValue")
            ->addSelect('(TRUE)')
            ->addSelect('u.createdAt')
            ->addSelect('u.id')
            ->addSelect('IDENTITY(u.organization)')
            ->leftJoin('u.groups', 'g')
            ->leftJoin('u.roles', 'r')
            ->andWhere('r = :role')
            ->groupBy('u.id')
            ->setParameter('role', $multiInsertRole);

        $affectedRecords = $this->queryExecutor->execute(
            'OroTestFrameworkBundle:Item',
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
        $this->assertEquals($expectedUserCount, $affectedRecords);

        /** @var Item[] $items */
        $items = $this->registry->getManagerForClass(Item::class)
            ->getRepository(Item::class)
            ->findAll();

        $this->assertNotEmpty($items);
        $this->assertCount(count($multiInsertRole->getUsers()), $items);
    }

    private function createUsers(Role $userRole, Organization $organization, int $userCount): void
    {
        $userManager = $this->getContainer()->get('oro_user.manager');
        for ($i = 1; $i <= $userCount; $i++) {
            /** @var User $user */
            $user = $userManager->createUser();
            $user->setUsername('multi_insert_user_' . $i)
                ->setPlainPassword('simple_password')
                ->setEmail('multi_insert_user_' . $i . '@example.com')
                ->setFirstName('First Name')
                ->setLastName('Last Name')
                ->setOrganization($organization)
                ->setOrganizations(new ArrayCollection([$organization]))
                ->setOwner($organization->getBusinessUnits()->first())
                ->addRole($userRole)
                ->setEnabled(true)
            ;
            $userManager->updateUser($user, false);
        }
        $this->registry->getManagerForClass(User::class)->flush();
    }
}
