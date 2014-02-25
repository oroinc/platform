<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;

use JMS\Serializer\Annotation as Serializer;

/**
 * Workflow item
 *
 * @ORM\Table(
 *      name="oro_workflow_item",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="oro_workflow_item_entity_definition_unq",columns={"entity_id", "workflow_name"})
 *      },
 *      indexes={
 *          @ORM\Index(name="oro_workflow_item_workflow_name_idx", columns={"workflow_name"})
 *      }
 *  )
 * @ORM\Entity(repositoryClass="Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Serializer\ExclusionPolicy("all")
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class WorkflowItem
{
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     */
    protected $id;

    /**
     * Name of WorkflowDefinition
     *
     * @var string
     *
     * @ORM\Column(name="workflow_name", type="string", length=255)
     * @Serializer\Expose()
     */
    protected $workflowName;

    /**
     * @var int
     *
     * @ORM\Column(name="entity_id", type="integer", nullable=true)
     * @Serializer\Expose()
     */
    protected $entityId;

    /**
     * @var WorkflowStep
     *
     * @ORM\ManyToOne(targetEntity="WorkflowStep")
     * @ORM\JoinColumn(name="current_step_id", referencedColumnName="id")
     */
    protected $currentStep;

    /**
     * @var string
     *
     * @ORM\Column(name="closed", type="boolean")
     * @Serializer\Expose()
     */
    protected $closed;

    /**
     * Corresponding Workflow Definition
     *
     * @var WorkflowDefinition
     *
     * @ORM\ManyToOne(targetEntity="WorkflowDefinition")
     * @ORM\JoinColumn(name="workflow_name", referencedColumnName="name", onDelete="CASCADE")
     */
    protected $definition;

    /**
     * Related transition records
     *
     * @var Collection|WorkflowTransitionRecord[]
     *
     * @ORM\OneToMany(
     *  targetEntity="WorkflowTransitionRecord",
     *  mappedBy="workflowItem",
     *  cascade={"persist", "remove"},
     *  orphanRemoval=true
     * )
     * @ORM\OrderBy({"transitionDate" = "ASC"})
     */
    protected $transitionRecords;

    /**
     * ACL identities of related entities
     *
     * @var Collection|WorkflowEntityAclIdentity[]
     *
     * @ORM\OneToMany(
     *  targetEntity="WorkflowEntityAclIdentity",
     *  mappedBy="workflowItem",
     *  cascade={"all"},
     *  orphanRemoval=true
     * )
     */
    protected $aclIdentities;

    /**
     * @var \Datetime $created
     *
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var \Datetime $updated
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated;

    /**
     * Serialized data of WorkflowItem
     *
     * @var string
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    protected $serializedData;

    /**
     * @var object
     */
    protected $entity;

    /**
     * @var WorkflowData
     */
    protected $data;

    /**
     * @var WorkflowResult
     *
     * @Serializer\Expose()
     */
    protected $result;

    /**
     * @var WorkflowAwareSerializer
     */
    protected $serializer;

    /**
     * @var string
     */
    protected $serializeFormat;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->transitionRecords = new ArrayCollection();
        $this->aclIdentities = new ArrayCollection();
        $this->closed = false;
        $this->data = new WorkflowData();
        $this->result = new WorkflowResult();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param int $id
     * @return WorkflowItem
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set workflowName
     *
     * @param string $workflowName
     * @return WorkflowItem
     */
    public function setWorkflowName($workflowName)
    {
        $this->workflowName = $workflowName;

        return $this;
    }

    /**
     * Get workflowName
     *
     * @return string
     */
    public function getWorkflowName()
    {
        return $this->workflowName;
    }

    /**
     * @param WorkflowStep $currentStep
     * @return WorkflowItem
     */
    public function setCurrentStep($currentStep)
    {
        $this->currentStep = $currentStep;

        return $this;
    }

    /**
     * @return WorkflowStep
     */
    public function getCurrentStep()
    {
        return $this->currentStep;
    }

    /**
     * This method should be called only from WorkflowDataSerializeSubscriber.
     *
     * @param int $entityId
     * @return WorkflowItem
     * @throws WorkflowException
     */
    public function setEntityId($entityId)
    {
        if ($this->entityId !== null && $this->entityId !== $entityId) {
            throw new WorkflowException('Workflow item entity ID can not be changed');
        }

        $this->entityId = $entityId;

        return $this;
    }

    /**
     * This method should be called only from WorkflowDataSerializeSubscriber.
     *
     * @return int
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Set closed
     *
     * @param boolean $closed
     * @return WorkflowItem
     */
    public function setClosed($closed)
    {
        $this->closed = (bool)$closed;

        return $this;
    }

    /**
     * Is closed
     *
     * @return string
     */
    public function isClosed()
    {
        return $this->closed;
    }

    /**
     * Set workflow definition
     *
     * @param WorkflowDefinition $definition
     * @return WorkflowItem
     */
    public function setDefinition(WorkflowDefinition $definition)
    {
        $this->definition = $definition;

        return $this;
    }

    /**
     * Get workflow definition
     *
     * @return WorkflowDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Set serialized data.
     *
     * This method should be called only from WorkflowDataSerializeSubscriber.
     *
     * @param string $data
     * @return WorkflowItem
     */
    public function setSerializedData($data)
    {
        $this->serializedData = $data;

        return $this;
    }

    /**
     * Get serialized data.
     *
     * This method should be called only from WorkflowDataSerializeSubscriber.
     *
     * @return string $data
     */
    public function getSerializedData()
    {
        return $this->serializedData;
    }

    /**
     * @param WorkflowData $data
     * @return WorkflowItem
     */
    public function setData(WorkflowData $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * This method should be called only during creation of WorkflowItem.
     *
     * @param object $entity
     * @return WorkflowItem
     * @throws WorkflowException
     */
    public function setEntity($entity)
    {
        if ($this->entity !== null && $this->entity !== $entity) {
            throw new WorkflowException('Workflow item entity can not be changed');
        }

        $this->entity = $entity;

        return $this;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Get data
     *
     * @return WorkflowData
     * @throws WorkflowException If data cannot be deserialized
     */
    public function getData()
    {
        if (!$this->data) {
            if (!$this->serializedData) {
                $this->data = new WorkflowData();
            } elseif (!$this->serializer) {
                throw new WorkflowException('Cannot deserialize data of workflow item. Serializer is not available.');
            } else {
                $this->serializer->setWorkflowName($this->workflowName);
                $this->data = $this->serializer->deserialize(
                    $this->serializedData,
                    'Oro\Bundle\WorkflowBundle\Model\WorkflowData',
                    $this->serializeFormat
                );
                $this->data->set($this->getDefinition()->getEntityAttributeName(), $this->getEntity(), false);
            }
        }
        return $this->data;
    }

    /**
     * Set serializer.
     *
     * This method should be called only from WorkflowDataSerializeSubscriber.
     *
     * @param WorkflowAwareSerializer $serializer
     * @param string $format
     */
    public function setSerializer(WorkflowAwareSerializer $serializer, $format)
    {
        $this->serializer = $serializer;
        $this->serializeFormat = $format;
    }

    /**
     * @return WorkflowResult
     */
    public function getResult()
    {
        if (!$this->result) {
            $this->result = new WorkflowResult();
        }
        return $this->result;
    }

    /**
     * @return Collection|WorkflowTransitionRecord[]
     */
    public function getTransitionRecords()
    {
        return $this->transitionRecords;
    }

    /**
     * @param WorkflowTransitionRecord $transitionRecord
     * @return WorkflowItem
     */
    public function addTransitionRecord(WorkflowTransitionRecord $transitionRecord)
    {
        $transitionRecord->setWorkflowItem($this);
        $this->transitionRecords->add($transitionRecord);

        return $this;
    }

    /**
     * @return WorkflowEntityAclIdentity[]|Collection
     */
    public function getAclIdentities()
    {
        return $this->aclIdentities;
    }

    /**
     * @param WorkflowEntityAclIdentity[]|Collection $aclIdentities
     * @return WorkflowItem
     */
    public function setAclIdentities($aclIdentities)
    {
        $newAttributeSteps = array();
        foreach ($aclIdentities as $aclIdentity) {
            $newAttributeSteps[] = $aclIdentity->getAclAttributeStepKey();
        }

        foreach ($this->aclIdentities as $aclIdentity) {
            if (!in_array($aclIdentity->getAclAttributeStepKey(), $newAttributeSteps)) {
                $this->removeEntityAcl($aclIdentity);
            }
        }

        foreach ($aclIdentities as $aclIdentity) {
            $this->addEntityAcl($aclIdentity);
        }

        return $this;
    }

    /**
     * @param WorkflowEntityAclIdentity $aclIdentity
     * @return WorkflowItem
     */
    public function addEntityAcl(WorkflowEntityAclIdentity $aclIdentity)
    {
        $attributeStep = $aclIdentity->getAclAttributeStepKey();

        if (!$this->hasAclIdentityByAttribute($attributeStep)) {
            $aclIdentity->setWorkflowItem($this);
            $this->aclIdentities->add($aclIdentity);
        } else {
            $this->getAclIdentityByAttributeStep($attributeStep)->import($aclIdentity);
        }

        return $this;
    }

    /**
     * @param WorkflowEntityAclIdentity $aclIdentity
     * @return WorkflowItem
     */
    public function removeEntityAcl(WorkflowEntityAclIdentity $aclIdentity)
    {
        $attributeStep = $aclIdentity->getAclAttributeStepKey();

        if ($this->hasAclIdentityByAttribute($attributeStep)) {
            $aclIdentity = $this->getAclIdentityByAttributeStep($attributeStep);
            $this->aclIdentities->removeElement($aclIdentity);
        }

        return $this;
    }

    /**
     * @param string $attribute
     * @return bool
     */
    public function hasAclIdentityByAttribute($attribute)
    {
        return $this->getAclIdentityByAttributeStep($attribute) !== null;
    }

    /**
     * @param string $attributeStep
     * @return null|WorkflowEntityAclIdentity
     */
    public function getAclIdentityByAttributeStep($attributeStep)
    {
        foreach ($this->aclIdentities as $aclIdentity) {
            if ($aclIdentity->getAclAttributeStepKey() == $attributeStep) {
                return $aclIdentity;
            }
        }

        return null;
    }

    /**
     * Get created date/time
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created;
    }

    /**
     * Get last update date/time
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated;
    }

    /**
     * Pre persist event listener
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->created = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->setUpdated();
    }

    /**
     * Invoked before the entity is updated.
     *
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->setUpdated();
    }

    /**
     * Set updated property to actual Date
     */
    public function setUpdated()
    {
        $this->updated = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
