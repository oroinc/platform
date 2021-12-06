<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Command\LoadWorkflowDefinitionsCommand;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

abstract class WorkflowTestCase extends WebTestCase
{
    /**
     * Loads workflow by command from workflow.yml file under specified directory.
     */
    protected function loadWorkflowFrom(string $directory): string
    {
        self::getContainer()->get('oro_workflow.configuration.workflow_config_finder.builder')
            ->setSubDirectory($directory);

        return self::runCommand(LoadWorkflowDefinitionsCommand::getDefaultName(), [], true, true);
    }

    protected function getSystemWorkflowManager(): WorkflowManager
    {
        return self::getContainer()->get('oro_workflow.registry.workflow_manager')->getManager('system');
    }

    protected function getSystemWorkflowRegistry(): WorkflowRegistry
    {
        return self::getContainer()->get('oro_workflow.registry.system');
    }

    protected function getEntityManager(string $class): EntityManagerInterface
    {
        $doctrineHelper = self::getContainer()->get('oro_entity.doctrine_helper');

        return $doctrineHelper->getEntityManagerForClass($class);
    }

    protected function assertWorkflowItemsCount(int $expected, string $workflowName = null): void
    {
        $criteria = ['entityClass' => WorkflowAwareEntity::class];

        if ($workflowName) {
            $criteria['workflowName'] = $workflowName;
        }
        $class = WorkflowItem::class;
        $this->assertCount($expected, $this->getEntityManager($class)->getRepository($class)->findBy($criteria));
    }

    protected function assertWorkflowTransitionRecordCount(int $expected): void
    {
        $class = WorkflowTransitionRecord::class;
        $this->assertCount($expected, $this->getEntityManager($class)->getRepository($class)->findAll());
    }

    protected function createWorkflowAwareEntity(bool $flush = true): WorkflowAwareEntity
    {
        $em = $this->getEntityManager(WorkflowAwareEntity::class);
        $entity = new WorkflowAwareEntity();
        $em->persist($entity);
        if ($flush) {
            $em->flush($entity);
        }

        return $entity;
    }
}
