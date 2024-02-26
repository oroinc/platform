<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowEntityAclRepository;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

/**
* Entity that represents Workflow Entity Acl
*
*/
#[ORM\Entity(repositoryClass: WorkflowEntityAclRepository::class)]
#[ORM\Table(name: 'oro_workflow_entity_acl')]
#[ORM\UniqueConstraint(
    name: 'oro_workflow_acl_unique_idx',
    columns: ['workflow_name', 'attribute', 'workflow_step_id']
)]
class WorkflowEntityAcl
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WorkflowDefinition::class, inversedBy: 'entityAcls')]
    #[ORM\JoinColumn(name: 'workflow_name', referencedColumnName: 'name', onDelete: 'CASCADE')]
    protected ?WorkflowDefinition $definition = null;

    #[ORM\Column(name: 'attribute', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $attribute = null;

    #[ORM\ManyToOne(targetEntity: WorkflowStep::class)]
    #[ORM\JoinColumn(name: 'workflow_step_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?WorkflowStep $step = null;

    #[ORM\Column(name: 'entity_class', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $entityClass = null;

    #[ORM\Column(name: 'updatable', type: Types::BOOLEAN)]
    protected ?bool $updatable = true;

    #[ORM\Column(name: 'deletable', type: Types::BOOLEAN)]
    protected ?bool $deletable = true;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param WorkflowDefinition $definition
     * @return WorkflowEntityAcl
     */
    public function setDefinition(WorkflowDefinition $definition)
    {
        $this->definition = $definition;

        return $this;
    }

    /**
     * @return WorkflowDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param string $attribute
     * @return WorkflowEntityAcl
     */
    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * @return string
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @param WorkflowStep $step
     * @return WorkflowEntityAcl
     */
    public function setStep($step)
    {
        $this->step = $step;

        return $this;
    }

    /**
     * @return WorkflowStep
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @return string
     * @throws WorkflowException
     */
    public function getAttributeStepKey()
    {
        $attribute = $this->getAttribute();
        $step = $this->getStep();
        if (!$step) {
            throw new WorkflowException(
                sprintf('Workflow entity ACL with ID %s doesn\'t have workflow step', $this->getId())
            );
        }

        return sprintf('attribute_%s_step_%s', $attribute, $step->getName());
    }

    /**
     * @param mixed $className
     * @return WorkflowEntityAcl
     */
    public function setEntityClass($className)
    {
        $this->entityClass = $className;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @param boolean $deletable
     * @return WorkflowEntityAcl
     */
    public function setDeletable($deletable)
    {
        $this->deletable = $deletable;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isDeletable()
    {
        return $this->deletable;
    }

    /**
     * @param boolean $updatable
     * @return WorkflowEntityAcl
     */
    public function setUpdatable($updatable)
    {
        $this->updatable = $updatable;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isUpdatable()
    {
        return $this->updatable;
    }

    /**
     * @param WorkflowEntityAcl $acl
     * @return WorkflowEntityAcl
     */
    public function import(WorkflowEntityAcl $acl)
    {
        $this->setAttribute($acl->getAttribute())
            ->setStep($this->getDefinition()->getStepByName($acl->getStep()->getName()))
            ->setEntityClass($acl->getEntityClass())
            ->setUpdatable($acl->isUpdatable())
            ->setDeletable($acl->isDeletable());

        return $this;
    }
}
