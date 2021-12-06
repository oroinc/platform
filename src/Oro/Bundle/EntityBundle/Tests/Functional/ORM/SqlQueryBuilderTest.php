<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\ORM;

use Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DoctrineUtils\ORM\ResultSetMappingUtil;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;

/**
 * @dbIsolationPerTest
 */
class SqlQueryBuilderTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadUserData::class
        ]);
    }

    public function testSimpleUpdate()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $qb = $this->createSqlQueryBuilder();
        $qb->update('oro_user', 'u')
            ->set('first_name', ':newFN')
            ->where($qb->expr()->eq('u.id', ':id'))
            ->setParameter('newFN', 'UPDATE FN1')
            ->setParameter('id', $user->getId());

        $qb->execute();

        $result = $this->getUserFirstName($user);
        $this->assertEquals([['first_name' => 'UPDATE FN1']], $result);
    }

    public function testSimpleUpdateWithoutTableAlias()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $qb = $this->createSqlQueryBuilder();
        $qb->update('oro_user')
            ->set('first_name', ':newFN')
            ->where($qb->expr()->eq('id', ':id'))
            ->setParameter('newFN', 'UPDATE FN1')
            ->setParameter('id', $user->getId());

        $qb->execute();

        $result = $this->getUserFirstName($user, true);
        $this->assertEquals([['first_name' => 'UPDATE FN1']], $result);
    }

    public function testUpdateWithOneJoin()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');
        $registry = $this->getContainer()->get('doctrine');
        $em = $registry->getManagerForClass(Organization::class);
        $organization = $em->getRepository(Organization::class)->getFirst();

        $qb = $this->createSqlQueryBuilder();
        $qb->update('oro_user', 'u')
            ->set('first_name', ':newFN')
            ->join('u', 'oro_organization', 'org', 'u.organization_id = org.id')
            ->where($qb->expr()->eq('u.id', ':id'))
            ->andWhere($qb->expr()->eq('org.id', ':orgId'))
            ->setParameter('newFN', 'UPDATE FN2')
            ->setParameter('id', $user->getId())
            ->setParameter('orgId', $organization->getId());

        $qb->execute();

        $result = $this->getUserFirstName($user);
        $this->assertEquals([['first_name' => 'UPDATE FN2']], $result);
    }

    public function testUpdateWithTwoJoinsOneToBaseTableAndQueryExecuteWithParameters()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');
        $registry = $this->getContainer()->get('doctrine');
        $em = $registry->getManagerForClass(Organization::class);
        /** @var Organization $organization */
        $organization = $em->getRepository(Organization::class)->getFirst();
        $bu = $organization->getBusinessUnits()->first();

        $qb = $this->createSqlQueryBuilder();
        $qb->update('oro_user', 'u')
            ->set('first_name', ':newFN')
            ->innerJoin('u', 'oro_business_unit', 'bu', 'u.business_unit_owner_id = bu.id')
            ->join('bu', 'oro_organization', 'org', 'bu.organization_id = org.id')
            ->where($qb->expr()->eq('u.id', ':id'))
            ->andWhere($qb->expr()->eq('org.id', ':orgId'))
            ->andWhere($qb->expr()->eq('bu.id', ':buId'));

        $qb->getQuery()->execute(
            [
                'newFN' => 'UPDATE FN3',
                'id' => $user->getId(),
                'orgId' => $organization->getId(),
                'buId' => $bu->getId()
            ]
        );

        $result = $this->getUserFirstName($user);
        $this->assertEquals([['first_name' => 'UPDATE FN3']], $result);
    }

    public function testUpdateWithTwoJoinsOneToBaseTableWithoutWhere()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $qb = $this->createSqlQueryBuilder();
        $qb->update('oro_user', 'u')
            ->set('first_name', ':newFN')
            ->innerJoin('u', 'oro_business_unit', 'bu', 'u.business_unit_owner_id = bu.id')
            ->join('bu', 'oro_organization', 'org', 'bu.organization_id = org.id');

        $qb->getQuery()->execute(['newFN' => 'UPDATE FN4']);

        $result = $this->getUserFirstName($user);
        $this->assertEquals([['first_name' => 'UPDATE FN4']], $result);
    }

    public function testUpdateWithTwoJoinsToBaseTableAndQueryExecuteWithParameters()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');
        $registry = $this->getContainer()->get('doctrine');
        $em = $registry->getManagerForClass(Organization::class);
        /** @var Organization $organization */
        $organization = $em->getRepository(Organization::class)->getFirst();
        $bu = $organization->getBusinessUnits()->first();

        $qb = $this->createSqlQueryBuilder();
        $qb->update('oro_user', 'u')
            ->set('first_name', ':newFN')
            ->join('u', 'oro_organization', 'org', 'u.organization_id = org.id')
            ->innerJoin('u', 'oro_business_unit', 'bu', 'u.business_unit_owner_id = bu.id')
            ->where($qb->expr()->eq('u.id', ':id'))
            ->andWhere($qb->expr()->eq('org.id', ':orgId'))
            ->andWhere($qb->expr()->eq('bu.id', ':buId'));

        $qb->getQuery()->execute(
            [
                'newFN' => 'UPDATE FN5',
                'id' => $user->getId(),
                'orgId' => $organization->getId(),
                'buId' => $bu->getId()
            ]
        );

        $result = $this->getUserFirstName($user);
        $this->assertEquals([['first_name' => 'UPDATE FN5']], $result);
    }

    private function createSqlQueryBuilder(): SqlQueryBuilder
    {
        $registry = $this->getContainer()->get('doctrine');
        $em = $registry->getManagerForClass(User::class);

        $rsm = ResultSetMappingUtil::createResultSetMapping($em->getConnection()->getDatabasePlatform());
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('first_name', 'first_name');

        return new SqlQueryBuilder($em, $rsm);
    }

    private function getUserFirstName(User $user, bool $withoutTableAlias = false): array
    {
        $qb = $this->createSqlQueryBuilder();
        if ($withoutTableAlias) {
            $qb
                ->select('first_name')
                ->from('oro_user')
                ->where('id = :id');
        } else {
            $qb
                ->select('u.first_name')
                ->from('oro_user', 'u')
                ->where('u.id = :id');
        }
        $qb->setParameter('id', $user->getId());

        return $qb->getQuery()->getArrayResult();
    }
}
