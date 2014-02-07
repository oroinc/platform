<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *      name="oro_workflow_entity_acl",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="oro_workflow_acl_unique_idx", columns={"workflow_name", "attribute"})
 *      }
 * )
 * @ORM\Entity
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
     * @var string
     *
     * @ORM\Column(name="class_name", type="string", length=255, nullable=false)
     */
    protected $className;

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
     * @return WorkflowStep
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
     * @param mixed $className
     * @return WorkflowEntityAcl
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getClassName()
    {
        return $this->className;
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
            ->setClassName($acl->getClassName())
            ->setUpdatable($acl->isUpdatable())
            ->setDeletable($acl->isDeletable());

        return $this;
    }
}
