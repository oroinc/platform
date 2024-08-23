<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessDefinitionRepository;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

/**
 * A process entity
 */
#[ORM\Entity(repositoryClass: ProcessDefinitionRepository::class)]
#[ORM\Table('oro_process_definition')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    mode: 'hidden',
    routeName: 'oro_process_definition_index',
    routeView: 'oro_process_definition_view',
    defaultValues: [
        'entity' => ['icon' => 'fa-inbox'],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management'],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true]
    ]
)]
class ProcessDefinition implements DomainObjectInterface
{
    #[ORM\Id]
    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    protected ?string $name = null;

    #[ORM\Column(name: 'label', type: Types::STRING, length: 255)]
    protected ?string $label = null;

    #[ORM\Column(name: 'enabled', type: Types::BOOLEAN)]
    protected ?bool $enabled = true;

    #[ORM\Column(name: 'related_entity', type: Types::STRING, length: 255)]
    protected ?string $relatedEntity = null;

    #[ORM\Column(name: 'execution_order', type: Types::SMALLINT)]
    protected ?int $executionOrder = 0;

    /**
     * @var array
     */
    #[ORM\Column(name: 'exclude_definitions', type: Types::SIMPLE_ARRAY, nullable: true)]
    protected $excludeDefinitions = [];

    /**
     * @var array
     */
    #[ORM\Column(name: 'pre_conditions_configuration', type: Types::JSON, nullable: true)]
    protected $preConditionsConfiguration;

    /**
     * @var array
     */
    #[ORM\Column(name: 'actions_configuration', type: Types::JSON)]
    protected $actionsConfiguration = [];

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updatedAt = null;

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

    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->preUpdate();
    }

    #[ORM\PreUpdate]
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
