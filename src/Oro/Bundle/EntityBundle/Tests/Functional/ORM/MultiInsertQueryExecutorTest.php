<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\MultiInsertQueryExecutor;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class MultiInsertQueryExecutorTest extends WebTestCase
{
    private const BATCH_SIZE = 1;

    /** @var MultiInsertQueryExecutor */
    private $queryExecutor;

    protected function setUp(): void
    {
        $this->initClient();
        $this->queryExecutor = $this->getContainer()->get('oro_entity.orm.multi_insert_query_executor');
        $this->queryExecutor->setBatchSize(self::BATCH_SIZE);
    }

    private function getRepository(string $entityClass): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository($entityClass);
    }

    private function getOrganization(): Organization
    {
        /** @var OrganizationRepository $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(Organization::class);

        return $repository->getFirst();
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

    public function testInsertMultipleBatches()
    {
        $decimalValue = 12345678.29;
        $expectedUserCount = self::BATCH_SIZE + 1;
        $multiInsertRole = new Role();
        $multiInsertRole->setLabel('statusMultiInsertRole');
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManagerForClass(Role::class);
        $em->persist($multiInsertRole);
        $this->createUsers($multiInsertRole, $this->getOrganization(), $expectedUserCount);
        $em->refresh($multiInsertRole);

        $queryBuilder = $this->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('u.email')
            ->addSelect('u.id')
            ->addSelect((string)$decimalValue)
            ->addSelect('(TRUE)')
            ->addSelect('u.createdAt')
            ->addSelect('u.id')
            ->addSelect('IDENTITY(u.organization)')
            ->leftJoin('u.groups', 'g')
            ->leftJoin('u.userRoles', 'r')
            ->andWhere('r = :role')
            ->groupBy('u.id')
            ->setParameter('role', $multiInsertRole);

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
        $this->assertEquals($expectedUserCount, $affectedRecords);

        /** @var Item[] $items */
        $items = $this->getRepository(Item::class)
            ->findAll();

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
                ->addUserRole($userRole)
                ->setEnabled(true);
            $userManager->updateUser($user, false);
        }

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManagerForClass(User::class);
        $em->flush();
    }
}
