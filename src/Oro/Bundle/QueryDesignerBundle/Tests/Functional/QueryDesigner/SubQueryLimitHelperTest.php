<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Functional\QueryDesigner;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\SqlWalker;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\SubQueryLimitHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SubQueryLimitHelperTest extends WebTestCase
{
    /** @var SubQueryLimitHelper */
    private $helper;

    protected function setUp()
    {
        $this->markTestSkipped('Broken functionality, will be fixed in BAP-17718');
        $this->initClient();
        $this->helper = new SubQueryLimitHelper();
    }

    public function testSetLimit()
    {
        $testQb = $this->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepository(WorkflowAwareEntity::class)
            ->createQueryBuilder('testQb');

        $testQb = $this->helper->setLimit($testQb, 777, 'id');
        $this->assertEquals(SqlWalker::class, $testQb->getQuery()->getHint(Query::HINT_CUSTOM_OUTPUT_WALKER));
        $this->assertEquals(777, $testQb->getQuery()->getHint(SqlWalker::WALKER_HOOK_LIMIT_VALUE));
        $this->assertEquals('id', $testQb->getQuery()->getHint(SqlWalker::WALKER_HOOK_LIMIT_ID));
        $this->assertContains(
            SqlWalker::WALKER_HOOK_LIMIT_KEY,
            $testQb->getQuery()->getHint(SqlWalker::WALKER_HOOK_LIMIT_KEY)
        );
    }

    public function testEnvironmentConfiguration()
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(WorkflowAwareEntity::class);

        $repo = $em->getRepository(WorkflowAwareEntity::class);
        $testQb = $repo->createQueryBuilder('testQb');

        $testQb = $this->helper->setLimit($testQb, 777, 'id');
        $this->assertEquals(SqlWalker::class, $testQb->getQuery()->getHint(Query::HINT_CUSTOM_OUTPUT_WALKER));

        $deleteQb = $em->createQueryBuilder()->delete(WorkflowAwareEntity::class, 'we')->where('we.id = 0');
        $this->assertNotEquals(SqlWalker::class, $deleteQb->getQuery()->getHint(Query::HINT_CUSTOM_OUTPUT_WALKER));

        $deleteQb->getQuery()->execute();
    }
}
