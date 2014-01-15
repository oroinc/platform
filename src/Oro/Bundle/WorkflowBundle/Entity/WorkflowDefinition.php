<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(
 *      name="oro_workflow_definition", indexes={
 *          @ORM\Index(name="oro_workflow_definition_enabled_idx", columns={"enabled"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class WorkflowDefinition
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $label;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $type;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $enabled;

    /**
     * @var array
     *
     * @ORM\Column(name="configuration", type="array")
     */
    protected $configuration;

    /**
     * @var WorkflowStep[]|Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="WorkflowStep",
     *      mappedBy="definition",
     *      orphanRemoval=true,
     *      cascade={"all"}
     * )
     */
    protected $steps;

    /**
     * @var WorkflowStep
     *
     * @ORM\ManyToOne(targetEntity="WorkflowStep")
     * @ORM\JoinColumn(name="start_step_id", referencedColumnName="id")
     */
    protected $startStep;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="WorkflowDefinitionEntity",
     *      mappedBy="workflowDefinition",
     *      orphanRemoval=true,
     *      cascade={"all"}
     * )
     */
    protected $workflowDefinitionEntities;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->enabled = false;
        $this->configuration = array();
        $this->steps = new ArrayCollection();
        $this->workflowDefinitionEntities = new ArrayCollection();
    }

    /**
     * Set name
     *
     * @param string $name
     * @return WorkflowDefinition
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set label
     *
     * @param string $label
     * @return WorkflowDefinition
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return WorkflowDefinition
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     * @return WorkflowDefinition
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool)$enabled;

        return $this;
    }

    /**
     * Is enabled
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set configuration
     *
     * @param array $configuration
     * @return WorkflowDefinition
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;

        return $this;
    }

    /**
     * Get configuration
     *
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param WorkflowStep $startStep
     * @return WorkflowDefinition
     * @throws \LogicException
     */
    public function setStartStep($startStep)
    {
        if (null !== $startStep) {
            $stepName = $startStep->getName();

            if (!$this->hasStepByName($stepName)) {
                throw new \LogicException(
                    sprintf('Workflow "%s" does not contain step "%s"', $this->getName(), $stepName)
                );
            }

            $this->startStep = $this->getStepByName($stepName);
        } else {
            $this->startStep = null;
        }

        return $this;
    }

    /**
     * @return WorkflowStep
     */
    public function getStartStep()
    {
        return $this->startStep;
    }

    /**
     * @return WorkflowStep[]
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * @param WorkflowStep[]|Collection $steps
     * @return WorkflowDefinition
     */
    public function setSteps($steps)
    {
        $newStepNames = array();
        foreach ($steps as $step) {
            $newStepNames[] = $step->getName();
        }

        foreach ($this->steps as $step) {
            if (!in_array($step->getName(), $newStepNames)) {
                $this->removeStep($step);
            }
        }

        foreach ($steps as $step) {
            $this->addStep($step);
        }

        return $this;
    }

    /**
     * @param WorkflowStep $step
     * @return WorkflowItem
     */
    public function addStep(WorkflowStep $step)
    {
        $stepName = $step->getName();

        if (!$this->hasStepByName($stepName)) {
            $step->setDefinition($this);
            $this->steps->add($step);
        }

        return $this;
    }

    /**
     * @param WorkflowStep $step
     * @return WorkflowItem
     */
    public function removeStep(WorkflowStep $step)
    {
        $stepName = $step->getName();

        if ($this->hasStepByName($stepName)) {
            $step = $this->getStepByName($stepName);
            $this->steps->removeElement($step);
        }

        return $this;
    }

    /**
     * @param string $stepName
     * @return bool
     */
    public function hasStepByName($stepName)
    {
        return $this->getStepByName($stepName) !== null;
    }

    /**
     * @param string $stepName
     * @return null|WorkflowStep
     */
    public function getStepByName($stepName)
    {
        foreach ($this->steps as $step) {
            if ($step->getName() == $stepName) {
                return $step;
            }
        }

        return null;
    }

    /**
     * @param Collection|WorkflowDefinitionEntity[] $definitionEntities
     * @return WorkflowDefinition
     */
    public function setWorkflowDefinitionEntities($definitionEntities)
    {
        /** @var WorkflowDefinitionEntity $entity */
        $newEntities = array();
        foreach ($definitionEntities as $entity) {
            $newEntities[$entity->getClassName()] = $entity;
        }

        foreach ($this->workflowDefinitionEntities as $entity) {
            if (array_key_exists($entity->getClassName(), $newEntities)) {
                unset($newEntities[$entity->getClassName()]);
            } else {
                $this->workflowDefinitionEntities->removeElement($entity);
            }
        }

        foreach ($newEntities as $entity) {
            $entity->setWorkflowDefinition($this);
            $this->workflowDefinitionEntities->add($entity);
        }

        return $this;
    }

    /**
     * @return Collection|WorkflowDefinitionEntity[]
     */
    public function getWorkflowDefinitionEntities()
    {
        return $this->workflowDefinitionEntities;
    }

    /**
     * @param WorkflowDefinition $definition
     * @return WorkflowDefinition
     */
    public function import(WorkflowDefinition $definition)
    {
        // enabled flag should not be imported
        $this->setName($definition->getName())
            ->setType($definition->getType())
            ->setLabel($definition->getLabel())
            ->setConfiguration($definition->getConfiguration())
            ->setSteps($definition->getSteps())
            ->setStartStep($definition->getStartStep())
            ->setWorkflowDefinitionEntities($definition->getWorkflowDefinitionEntities());

        return $this;
    }
}
