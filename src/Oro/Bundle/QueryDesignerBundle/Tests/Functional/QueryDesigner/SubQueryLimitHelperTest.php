<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Functional\QueryDesigner;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\SubQueryLimitHelper;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\SubQueryLimitOutputResultModifier;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\DoctrineUtils\ORM\Walker\SqlWalker;

/**
 * @dbIsolationPerTest
 */
class SubQueryLimitHelperTest extends WebTestCase
{
    private SubQueryLimitHelper $helper;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(['@OroQueryDesignerBundle/Tests/Functional/DataFixtures/workflows.yml']);
        $this->helper = self::getContainer()->get('oro_query_designer.query_designer.subquery_limit_helper');
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(WorkflowAwareEntity::class);
    }

    public function testSetLimit(): void
    {
        $qb = $this->getEntityManager()->getRepository(WorkflowAwareEntity::class)->createQueryBuilder('e');

        $qb = $this->helper->setLimit($qb, 777, 'id');

        $testQuery = $qb->getQuery();
        self::assertEquals(SqlWalker::class, $testQuery->getHint(Query::HINT_CUSTOM_OUTPUT_WALKER));
        self::assertEquals(
            [
                [sprintf('\'%1$s0\' = \'%1$s0\'', SubQueryLimitOutputResultModifier::WALKER_HOOK_LIMIT_KEY), 777, 'id']
            ],
            $testQuery->getHint(SubQueryLimitOutputResultModifier::WALKER_HOOK_LIMIT_KEY)
        );
    }

    public function testSetLimitForOneSubQuery(): void
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository(WorkflowAwareEntity::class);

        $subSelectQb = $repo->createQueryBuilder('subquery1');
        $this->helper->setLimit($subSelectQb, 4, 'id');

        $selectQb = $repo->createQueryBuilder('e');
        $selectQb->where('e.id IN (' . $subSelectQb->getDQL() . ')');

        self::assertCount(4, $selectQb->getQuery()->getResult());
    }

    public function testSetLimitForSeveralSubQueries(): void
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository(WorkflowAwareEntity::class);

        $subSelect1Qb = $repo->createQueryBuilder('subquery1');
        $this->helper->setLimit($subSelect1Qb, 4, 'id');

        $subSelect2Qb = $repo->createQueryBuilder('subquery2');
        $this->helper->setLimit($subSelect2Qb, 2, 'id');

        $selectQb = $repo->createQueryBuilder('e');
        $selectQb->where('e.id IN (' . $subSelect1Qb->getDQL() . ')');
        $selectQb->orWhere('e.id IN (' . $subSelect2Qb->getDQL() . ')');

        self::assertCount(4, $selectQb->getQuery()->getResult());
    }

    public function testDeleteAfterSetLimit(): void
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository(WorkflowAwareEntity::class);

        $subSelectQb = $repo->createQueryBuilder('subquery');
        $this->helper->setLimit($subSelectQb, 4, 'id');

        $selectQb = $repo->createQueryBuilder('e');
        $selectQb->where('e.id IN (' . $subSelectQb->getDQL() . ')');

        self::assertCount(4, $selectQb->getQuery()->getResult());

        $deleteQb = $em->createQueryBuilder()->delete(WorkflowAwareEntity::class, 'e');
        $deleteQb->getQuery()->execute();

        self::assertCount(0, $repo->findAll());
    }
}
