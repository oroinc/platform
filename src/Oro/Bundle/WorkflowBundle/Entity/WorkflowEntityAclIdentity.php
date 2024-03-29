<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowEntityAclIdentityRepository;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

/**
* Entity that represents Workflow Entity Acl Identity
*
*/
#[ORM\Entity(repositoryClass: WorkflowEntityAclIdentityRepository::class)]
#[ORM\Table(name: 'oro_workflow_entity_acl_ident')]
#[ORM\Index(columns: ['entity_id', 'entity_class'], name: 'oro_workflow_entity_acl_identity_idx')]
#[ORM\UniqueConstraint(
    name: 'oro_workflow_entity_acl_identity_unique_idx',
    columns: ['workflow_entity_acl_id', 'entity_id', 'workflow_item_id']
)]
class WorkflowEntityAclIdentity
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WorkflowEntityAcl::class)]
    #[ORM\JoinColumn(name: 'workflow_entity_acl_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?WorkflowEntityAcl $acl = null;

    #[ORM\Column(name: 'entity_class', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $entityClass = null;

    #[ORM\Column(name: 'entity_id', type: Types::INTEGER, nullable: false)]
    protected ?int $entityId = null;

    #[ORM\ManyToOne(targetEntity: WorkflowItem::class, inversedBy: 'aclIdentities')]
    #[ORM\JoinColumn(name: 'workflow_item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?WorkflowItem $workflowItem = null;

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
