<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *      name="oro_workflow_restriction",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="oro_workflow_restriction_idx",
 *              columns={"workflow_name", "workflow_step_id", "field", "entity_class", "mode"}
 *          )
 *      }
 * )
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowRestrictionRepository")
 */
class WorkflowRestriction
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
     * @ORM\ManyToOne(targetEntity="WorkflowDefinition", inversedBy="restrictions")
     * @ORM\JoinColumn(name="workflow_name", referencedColumnName="name", onDelete="CASCADE", nullable=false)
     */
    protected $definition;

    /**
     * @var string
     *
     * @ORM\Column(name="field", type="string", length=255, nullable=false)
     */
    protected $field;

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
     * @var string
     *
     * @ORM\Column(name="mode", type="string", length=255)
     */
    protected $mode;
    
    /**
     * @var array
     *
     * @ORM\Column(name="mode_values", type="json_array", nullable=true)
     */
    protected $values;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param WorkflowDefinition $definition
     *
     * @return $this
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
     * @param string $field
     *
     * @return $this
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param WorkflowStep $step
     *
     * @return $this
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
     * @param string $className
     *
     * @return $this
     */
    public function setEntityClass($className)
    {
        $this->entityClass = $className;

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
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     *
     * @return $this
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
        
        return $this;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param array $values
     *
     * @return $this
     */
    public function setValues(array $values)
    {
        $this->values = $values;
        
        return $this;
    }

    /**
     * @return string
     */
    public function getHashKey()
    {
        $stepName       = $this->getStep() ? $this->getStep()->getName() : null;
        $hashParams = [
            $stepName,
            $this->getField(),
            $this->getEntityClass(),
            $this->getMode()
        ];

        return md5(json_encode($hashParams));
    }

    /**
     * @param WorkflowRestriction $restriction
     *
     * @return WorkflowEntityAcl
     *
     */
    public function import(WorkflowRestriction $restriction)
    {
        $this
            ->setField($restriction->getField())
            ->setEntityClass($restriction->getEntityClass())
            ->setMode($restriction->getMode())
            ->setValues($restriction->getValues());

        return $this;
    }
}
