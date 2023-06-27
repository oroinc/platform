<?php

namespace Oro\Component\DoctrineUtils\Tests\Functional\ORM;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DoctrineUtils\ORM\UnionQueryBuilder;

class UnionQueryBuilderTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(
            ['@OroUserBundle/Tests/Functional/DataFixtures/Alice/users_enable_switch_action_handler_users.yml']
        );
    }

    public function testUnionQueryWithParameters(): void
    {
        $em = self::getContainer()->get('doctrine')->getManager();
        $unionQuery = new UnionQueryBuilder($em);

        $unionQuery->addSelect('id', 'id', Types::INTEGER)
            ->addSelect('dataName', 'dataName', Types::STRING);

        $subQb = $em->getRepository(BusinessUnit::class)->createQueryBuilder('b');
        $subQb->select('b.id AS id', 'b.name as dataName');
        $subQb->where($subQb->expr()->in('b.id', ':ids'));
        $subQb->andWhere('b.id != :nidq');
        $subQb->andWhere('b.id > :idq');
        $subQb->setParameter('nidq', -1);
        $businessUnitIds = [
            $this->getReference('business_unit.2')->getId(),
            $this->getReference('business_unit.4')->getId(),
            $this->getReference('business_unit.6')->getId()
        ];
        $subQb->setParameter('ids', $businessUnitIds);
        $subQb->setParameter('idq', 0);
        $unionQuery->addSubQuery($subQb->getQuery());

        $subQb = $em->getRepository(User::class)->createQueryBuilder('u');
        $subQb->select('u.id AS id', 'u.username as dataName');
        $subQb->where($subQb->expr()->in('u.id', ':ids'));
        $userIds = [
            $this->getReference('user.3')->getId(),
            $this->getReference('user.5')->getId(),
            $this->getReference('user.7')->getId()
        ];
        $subQb->setParameter('ids', $userIds);
        $unionQuery->addSubQuery($subQb->getQuery());

        $result = $unionQuery->getQuery()->getArrayResult();
        self::assertCount(6, $result);
        foreach ($result as $resultData) {
            if (str_starts_with($resultData['dataName'], 'business_unit')) {
                self::assertContains($resultData['id'], $businessUnitIds);
            } else {
                self::assertContains($resultData['id'], $userIds);
            }
        }
    }
}
