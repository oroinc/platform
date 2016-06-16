<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

/**
 * @ORM\Table("oro_process_definition")
 * @ORM\Entity(repositoryClass="Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessDefinitionRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      routeName="oro_process_definition_index",
 *      routeView="oro_process_definition_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-inbox",
 *              "category"="Workflow"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "note"={
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
class ProcessDefinition implements DomainObjectInterface
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
     * @ORM\Column(name="related_entity", type="string", length=255)
     */
    protected $relatedEntity;

    /**
     * @var integer
     *
     * @ORM\Column(name="execution_order", type="smallint")
     */
    protected $executionOrder = 0;

    /**
     * @var array
     *
     * @ORM\Column(name="exclude_definitions", type="simple_array", nullable=true)
     */
    protected $excludeDefinitions;

    /**
     * @var array
     *
     * @ORM\Column(name="pre_conditions_configuration", type="array", nullable=true)
     */
    protected $preConditionsConfiguration;

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
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
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
     * @param array $excludeDefinitions
     * @return ProcessDefinition
     */
    public function setExcludeDefinitions(array $excludeDefinitions)
    {
        $this->excludeDefinitions = $excludeDefinitions;

        return $this;
    }

    /**
     * @return array
     */
    public function getExcludeDefinitions()
    {
        return (array)$this->excludeDefinitions;
    }

    /**
     * @return array
     */
    public function getPreConditionsConfiguration()
    {
        return $this->preConditionsConfiguration;
    }

    /**
     * @param array $configuration
     * @return ProcessDefinition
     */
    public function setPreConditionsConfiguration($configuration)
    {
        $this->preConditionsConfiguration = $configuration;

        return $this;
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

    /**
     * @param ProcessDefinition $definition
     * @return ProcessDefinition
     */
    public function import(ProcessDefinition $definition)
    {
        // enabled flag should not be imported
        $this->setName($definition->getName())
            ->setLabel($definition->getLabel())
            ->setRelatedEntity($definition->getRelatedEntity())
            ->setExecutionOrder($definition->getExecutionOrder())
            ->setActionsConfiguration($definition->getActionsConfiguration())
            ->setExcludeDefinitions($definition->getExcludeDefinitions())
            ->setPreConditionsConfiguration($definition->getPreConditionsConfiguration());

        return $this;
    }

    /**
     * Returns a unique identifier for this domain object.
     *
     * @return string
     */
    public function getObjectIdentifier()
    {
        return $this->getName();
    }
}
