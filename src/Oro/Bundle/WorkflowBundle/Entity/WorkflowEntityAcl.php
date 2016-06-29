<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

/**
 * @ORM\Table(
 *      name="oro_workflow_entity_acl",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="oro_workflow_acl_unique_idx",
 *              columns={"workflow_name", "attribute", "workflow_step_id"}
 *          )
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowEntityAclRepository")
 */
class WorkflowEntityAcl
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
     * @var WorkflowDefinition
     *
     * @ORM\ManyToOne(targetEntity="WorkflowDefinition", inversedBy="entityAcls")
     * @ORM\JoinColumn(name="workflow_name", referencedColumnName="name", onDelete="CASCADE")
     */
    protected $definition;

    /**
     * @var string
     *
     * @ORM\Column(name="attribute", type="string", length=255, nullable=false)
     */
    protected $attribute;

    /**
     * @var WorkflowStep
     *
     * @ORM\ManyToOne(targetEntity="WorkflowStep")
     * @ORM\JoinColumn(name="workflow_step_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $step;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_class", type="string", length=255, nullable=false)
     */
    protected $entityClass;

    /**
     * @var boolean
     *
     * @ORM\Column(name="updatable", type="boolean")
     */
    protected $updatable = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="deletable", type="boolean")
     */
    protected $deletable = true;

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
