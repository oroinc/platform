<?php

namespace Oro\Bundle\WorkflowBundle\Acl;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAclIdentity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

/**
 * Provide functionality to interact between workflow entities and ACL entities
 */
class AclManager
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var WorkflowRegistry
     */
    protected $workflowRegistry;

    public function __construct(DoctrineHelper $doctrineHelper, WorkflowRegistry $workflowRegistry)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->workflowRegistry = $workflowRegistry;
    }

    /**
     * @param WorkflowItem $workflowItem
     * @return WorkflowItem
     * @throws WorkflowException
     */
    public function updateAclIdentities(WorkflowItem $workflowItem)
    {
        $workflow = $this->workflowRegistry->getWorkflow($workflowItem->getWorkflowName());
        $definition = $workflowItem->getDefinition();
        $currentStepName = $workflowItem->getCurrentStep()->getName();

        $aclIdentities = array();
        foreach ($definition->getEntityAcls() as $entityAcl) {
            if ($entityAcl->getStep()->getName() == $currentStepName) {
                $attributeName = $entityAcl->getAttribute();
                $attribute = $workflow->getAttributeManager()->getAttribute($attributeName);
                $entity = $workflowItem->getData()->get($attributeName);
                if (!$entity) {
                    continue;
                }

                if (!is_object($entity)) {
                    throw new WorkflowException(sprintf('Value of attribute "%s" must be an object', $attributeName));
                }

                $aclIdentity = new WorkflowEntityAclIdentity();
                $aclIdentity->setAcl($entityAcl)
                    ->setEntityClass($attribute->getOption('class'))
                    ->setEntityId($this->doctrineHelper->getSingleEntityIdentifier($entity));

                $aclIdentities[] = $aclIdentity;
            }
        }

        $workflowItem->setAclIdentities($aclIdentities);

        return $workflowItem;
    }
}
