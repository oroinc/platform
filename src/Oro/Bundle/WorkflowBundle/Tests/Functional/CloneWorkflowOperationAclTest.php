<?php

declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

/**
 * @dbIsolationPerTest
 */
class CloneWorkflowOperationAclTest extends WebTestCase
{
    use RolePermissionExtension;

    private const OPERATION_NAME = 'clone_workflow';

    private WorkflowDefinition $workflowDefinition;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->updateUserSecurityToken(self::AUTH_USER);
        $this->workflowDefinition = $this->createWorkflowDefinition();
    }

    public function testCloneIsAvailableWhenViewIsGranted(): void
    {
        self::assertTrue($this->isCloneOperationAvailable());
    }

    public function testCloneIsNotAvailableWhenViewIsDenied(): void
    {
        $this->updateRolePermission(
            'ROLE_ADMINISTRATOR',
            WorkflowDefinition::class,
            AccessLevel::NONE_LEVEL,
            'VIEW'
        );

        self::assertFalse($this->isCloneOperationAvailable());
    }

    private function isCloneOperationAvailable(): bool
    {
        $operation = self::getContainer()->get('oro_action.operation_registry')->findByName(self::OPERATION_NAME);
        self::assertInstanceOf(Operation::class, $operation);

        return $operation->isAvailable(new ActionData(['data' => $this->workflowDefinition]));
    }

    private function createWorkflowDefinition(): WorkflowDefinition
    {
        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition
            ->setName('clone_acl_test_flow')
            ->setLabel('Clone ACL Test Flow')
            ->setRelatedEntity(User::class)
            ->setEntityAttributeName('entity')
            ->setConfiguration([]);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($workflowDefinition);
        $entityManager->flush();

        return $workflowDefinition;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(WorkflowDefinition::class);
    }
}
