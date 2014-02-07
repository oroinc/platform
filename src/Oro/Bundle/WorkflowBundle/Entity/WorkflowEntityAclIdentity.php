<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

/**
 * @ORM\Table(
 *      name="oro_workflow_entity_acl_identity",
 *      indexes={
 *          @ORM\Index(
 *              name="oro_workflow_entity_acl_identity_idx",
 *              columns={"entity_id", "entity_class"}
 *          )
 *      },
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="oro_workflow_entity_acl_identity_unique_idx",
 *              columns={"workflow_entity_acl_id", "entity_id", "workflow_item_id"}
 *          )
 *      }
 * )
 * @ORM\Entity
 */
class WorkflowEntityAclIdentity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var WorkflowEntityAcl
     *
     * @ORM\ManyToOne(targetEntity="WorkflowEntityAcl")
     * @ORM\JoinColumn(name="workflow_entity_acl_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $acl;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_class", type="string", length=255, nullable=false)
     */
    protected $entityClass;

    /**
     * @var integer
     *
     * @ORM\Column(name="entity_id", type="integer", nullable=false)
     */
    protected $entityId;

    /**
     * @var WorkflowItem
     *
     * @ORM\ManyToOne(targetEntity="WorkflowItem", inversedBy="aclIdentities")
     * @ORM\JoinColumn(name="workflow_item_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $workflowItem;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param WorkflowEntityAcl $acl
     * @return WorkflowEntityAclIdentity
     */
    public function setAcl($acl)
    {
        $this->acl = $acl;

        return $this;
    }

    /**
     * @return WorkflowEntityAcl
     */
    public function getAcl()
    {
        return $this->acl;
    }

    /**
     * @return string
     * @throws WorkflowException
     */
    public function getAclAttributeStepKey()
    {
        $acl = $this->getAcl();
        if (!$acl) {
            throw new WorkflowException(
                sprintf('Workflow ACL identity with ID %s doesn\'t have entity ACL', $this->getId())
            );
        }

        return $acl->getAttributeStepKey();
    }

    /**
     * @param string $entityClass
     * @return WorkflowEntityAclIdentity
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @param int $entityId
     * @return WorkflowEntityAclIdentity
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @return int
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param WorkflowItem $workflowItem
     * @return WorkflowEntityAclIdentity
     */
    public function setWorkflowItem($workflowItem)
    {
        $this->workflowItem = $workflowItem;

        return $this;
    }

    /**
     * @return WorkflowItem
     */
    public function getWorkflowItem()
    {
        return $this->workflowItem;
    }

    /**
     * @param WorkflowEntityAclIdentity $aclIdentity
     * @return WorkflowEntityAclIdentity
     */
    public function import(WorkflowEntityAclIdentity $aclIdentity)
    {
        $this->setEntityId($aclIdentity->getEntityId());

        return $this;
    }
}
