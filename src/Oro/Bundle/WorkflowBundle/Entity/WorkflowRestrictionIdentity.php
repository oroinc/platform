<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *      name="oro_workflow_restriction_ident",
 *      indexes={
 *          @ORM\Index(
 *              name="oro_workflow_restr_ident_idx",
 *              columns={"entity_id"}
 *          )
 *      },
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="oro_workflow_restr_ident_unique_idx",
 *              columns={"workflow_restriction_id", "entity_id", "workflow_item_id"}
 *          )
 *      }
 * )
 * @ORM\Entity
 */
class WorkflowRestrictionIdentity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var WorkflowRestriction
     *
     * @ORM\ManyToOne(targetEntity="WorkflowRestriction", inversedBy="restrictionIdentities")
     * @ORM\JoinColumn(name="workflow_restriction_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $restriction;

    /**
     * @var int
     *
     * @ORM\Column(name="entity_id", type="integer", nullable=false)
     */
    protected $entityId;

    /**
     * @var WorkflowItem
     *
     * @ORM\ManyToOne(targetEntity="WorkflowItem", inversedBy="restrictionIdentities")
     * @ORM\JoinColumn(name="workflow_item_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $workflowItem;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param WorkflowRestriction $restriction
     *
     * @return $this
     */
    public function setRestriction(WorkflowRestriction $restriction)
    {
        $this->restriction = $restriction;

        return $this;
    }

    /**
     * @return WorkflowRestriction
     */
    public function getRestriction()
    {
        return $this->restriction;
    }

    /**
     * @param int $entityId
     *
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
     *
     * @return WorkflowRestrictionIdentity
     */
    public function setWorkflowItem(WorkflowItem $workflowItem)
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
}
