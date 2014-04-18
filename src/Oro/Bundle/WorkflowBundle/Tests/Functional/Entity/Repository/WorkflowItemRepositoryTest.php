<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\FunctionalTestCase;

use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;

/**
 * @db_isolation
 * @db_reindex
 */
class WorkflowItemRepositoryTest extends FunctionalTestCase
{
    /**
     * @var WorkflowItemRepository
     */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->getContainer()->get('doctrine')->getRepository('OroWorkflowBundle:WorkflowItem');
    }

    public function testResetWorkflowData()
    {
        $this->loadFixtures(
            array('Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowAwareEntities')
        );

        $rep = $this->getContainer()->get('doctrine')->getRepository('OroTestFrameworkBundle:WorkflowAwareEntity');
        $entities = $rep->findAll();
    }
}
