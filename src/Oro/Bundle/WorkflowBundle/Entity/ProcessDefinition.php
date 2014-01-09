<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("oro_process_definition")
 * @ORM\Entity
 */
class ProcessDefinition
{
    /**
     * @var string
     *
     * @ORM\Id
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
     * @var boolean
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    protected $enabled = true;

    /**
     * @var string
     *
     * @ORM\Column(name="related_entity", type="string", length=255, nullable=true)
     */
    protected $relatedEntity;

    /**
     * @var integer
     *
     * @ORM\Column(name="execution_order", type="smallint")
     */
    protected $executionOrder = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="execution_required", type="boolean")
     */
    protected $executionRequired = false;

    /**
     * @var array
     *
     * @ORM\Column(name="actions_configuration", type="array")
     */
    protected $actionsConfiguration;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @param string $name
     * @return ProcessDefinition
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
     * @return ProcessDefinition
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
     * @param boolean $enabled
     * @return ProcessDefinition
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param string $relatedEntity
     * @return ProcessDefinition
     */
    public function setRelatedEntity($relatedEntity)
    {
        $this->relatedEntity = $relatedEntity;

        return $this;
    }

    /**
     * @return string
     */
    public function getRelatedEntity()
    {
        return $this->relatedEntity;
    }

    /**
     * @param integer $executionOrder
     * @return ProcessDefinition
     */
    public function setExecutionOrder($executionOrder)
    {
        $this->executionOrder = $executionOrder;

        return $this;
    }

    /**
     * @return integer
     */
    public function getExecutionOrder()
    {
        return $this->executionOrder;
    }

    /**
     * @param boolean $executionRequired
     * @return ProcessDefinition
     */
    public function setExecutionRequired($executionRequired)
    {
        $this->executionRequired = $executionRequired;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isExecutionRequired()
    {
        return $this->executionRequired;
    }

    /**
     * @param array $configuration
     * @return ProcessDefinition
     */
    public function setActionsConfiguration($configuration)
    {
        $this->actionsConfiguration = $configuration;

        return $this;
    }

    /**
     * @return array
     */
    public function getActionsConfiguration()
    {
        return $this->actionsConfiguration;
    }

    /**
     * @param \DateTime $createdAt
     * @return ProcessDefinition
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return ProcessDefinition
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->preUpdate();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
