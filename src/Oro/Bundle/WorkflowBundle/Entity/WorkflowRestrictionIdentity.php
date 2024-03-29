<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Workflow Restriction Identity
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_workflow_restriction_ident')]
#[ORM\Index(columns: ['entity_id'], name: 'oro_workflow_restr_ident_idx')]
#[ORM\UniqueConstraint(
    name: 'oro_workflow_restr_ident_unique_idx',
    columns: ['workflow_restriction_id', 'entity_id', 'workflow_item_id']
)]
class WorkflowRestrictionIdentity
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WorkflowRestriction::class, inversedBy: 'restrictionIdentities')]
    #[ORM\JoinColumn(name: 'workflow_restriction_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?WorkflowRestriction $restriction = null;

    #[ORM\Column(name: 'entity_id', type: Types::INTEGER, nullable: false)]
    protected ?int $entityId = null;

    #[ORM\ManyToOne(targetEntity: WorkflowItem::class, inversedBy: 'restrictionIdentities')]
    #[ORM\JoinColumn(name: 'workflow_item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?WorkflowItem $workflowItem = null;

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
