<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table(
 *      name="oro_workflow_step",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="oro_workflow_step_unique_idx", columns={"workflow_name", "name"})
 *      },
 *      indexes={
 *          @ORM\Index(name="oro_workflow_step_name_idx", columns={"name"})
 *      }
 * )
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "note"={
 *              "immutable"=true
 *          },
 *          "comment"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          }
 *      }
 * )
 */
class WorkflowStep
{
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255)
     */
    protected $label;

    /**
     * @var integer
     *
     * @ORM\Column(name="step_order", type="integer")
     */
    protected $stepOrder = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_final", type="boolean")
     */
    protected $final = false;

    /**
     * @var WorkflowDefinition
     *
     * @ORM\ManyToOne(targetEntity="WorkflowDefinition", inversedBy="steps")
     * @ORM\JoinColumn(name="workflow_name", referencedColumnName="name", onDelete="CASCADE")
     */
    protected $definition;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @return WorkflowStep
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $label
     * @return WorkflowStep
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param int $order
     * @return WorkflowStep
     */
    public function setStepOrder($order)
    {
        $this->stepOrder = $order;

        return $this;
    }

    /**
     * @return int
     */
    public function getStepOrder()
    {
        return $this->stepOrder;
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
     * @param boolean $final
     * @return WorkflowStep
     */
    public function setFinal($final)
    {
        $this->final = $final;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isFinal()
    {
        return $this->final;
    }

    /**
     * @param WorkflowStep $workflowStep
     * @return WorkflowStep
     */
    public function import(WorkflowStep $workflowStep)
    {
        $this->setName($workflowStep->getName())
            ->setLabel($workflowStep->getLabel())
            ->setStepOrder($workflowStep->getStepOrder())
            ->setFinal($workflowStep->isFinal());

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->label;
    }
}
