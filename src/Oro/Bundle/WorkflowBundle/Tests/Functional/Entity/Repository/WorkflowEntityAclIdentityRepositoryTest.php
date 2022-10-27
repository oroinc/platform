<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowEntityAclIdentityRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAclIdentity;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowEntityAclIdentities;

class WorkflowEntityAclIdentityRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadWorkflowEntityAclIdentities::class]);
    }

    private function getRepository(): WorkflowEntityAclIdentityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(WorkflowEntityAclIdentity::class);
    }

    public function testFindByClassAndIdentifierAndActiveWorkflows(): void
    {
        $entity = $this->getReference('workflow_aware_entity.1');

        $result = $this->getRepository()->findByClassAndIdentifierAndActiveWorkflows(
            WorkflowAwareEntity::class,
            $entity->getId()
        );

        $this->assertCount(2, $result);
        $this->assertTrue($this->isWorkflowEntityAclIdentityExists($result, 'test_active_flow1', 'step1', 'name'));
        $this->assertTrue($this->isWorkflowEntityAclIdentityExists($result, 'test_active_flow2', 'step1', 'name'));

        $this->getContainer()
            ->get('oro_workflow.manager')
            ->deactivateWorkflow('test_active_flow1');

        $result = $this->getRepository()->findByClassAndIdentifierAndActiveWorkflows(
            WorkflowAwareEntity::class,
            $entity->getId()
        );

        $this->assertCount(1, $result);
        $this->assertFalse($this->isWorkflowEntityAclIdentityExists($result, 'test_active_flow1', 'step1', 'name'));
        $this->assertTrue($this->isWorkflowEntityAclIdentityExists($result, 'test_active_flow2', 'step1', 'name'));
    }

    private function isWorkflowEntityAclIdentityExists(array $data, string $workflow, string $step, string $attr): bool
    {
        $found = false;
        /** @var WorkflowEntityAclIdentity $identity */
        foreach ($data as $identity) {
            $acl = $identity->getAcl();

            if ($identity->getWorkflowItem()->getDefinition()->getName() === $workflow
                && $acl->getStep()->getName() === $step
                && $acl->getAttribute() === $attr
            ) {
                $found = true;
                break;
            }
        }

        return $found;
    }
}
