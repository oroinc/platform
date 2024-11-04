<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroWorkflowBundle_Entity_WorkflowItem;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\EntityAwareInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;
use Oro\Component\Action\Model\AbstractStorage;
use Oro\Component\Action\Model\ActionDataStorageAwareInterface;

/**
 * Workflow item
 *
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @mixin OroWorkflowBundle_Entity_WorkflowItem
 */
#[ORM\Entity(repositoryClass: WorkflowItemRepository::class)]
#[ORM\Table(name: 'oro_workflow_item')]
#[ORM\Index(columns: ['workflow_name'], name: 'oro_workflow_item_workflow_name_idx')]
#[ORM\Index(columns: ['entity_class', 'entity_id'], name: 'oro_workflow_item_entity_idx')]
#[ORM\UniqueConstraint(name: 'oro_workflow_item_entity_definition_unq', columns: ['entity_id', 'workflow_name'])]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'comment' => ['immutable' => true],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true]
    ]
)]
class WorkflowItem implements EntityAwareInterface, ExtendEntityInterface, ActionDataStorageAwareInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * Name of WorkflowDefinition
     */
    #[ORM\Column(name: 'workflow_name', type: Types::STRING, length: 255)]
    protected ?string $workflowName = null;

    #[ORM\Column(name: 'entity_id', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $entityId = null;

    #[ORM\Column(name: 'entity_class', type: Types::STRING, nullable: true)]
    protected ?string $entityClass = null;

    #[ORM\ManyToOne(targetEntity: WorkflowStep::class)]
    #[ORM\JoinColumn(name: 'current_step_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?WorkflowStep $currentStep = null;

    /**
     * Corresponding Workflow Definition
     */
    #[ORM\ManyToOne(targetEntity: WorkflowDefinition::class)]
    #[ORM\JoinColumn(name: 'workflow_name', referencedColumnName: 'name', onDelete: 'CASCADE')]
    protected ?WorkflowDefinition $definition = null;

    /**
     * Related transition records
     *
     * @var Collection<int, WorkflowTransitionRecord>
     */
    #[ORM\OneToMany(
        mappedBy: 'workflowItem',
        targetEntity: WorkflowTransitionRecord::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['transitionDate' => Criteria::ASC])]
    protected ?Collection $transitionRecords = null;

    /**
     * ACL identities of related entities
     *
     * @var Collection<int, WorkflowEntityAclIdentity>
     */
    #[ORM\OneToMany(
        mappedBy: 'workflowItem',
        targetEntity: WorkflowEntityAclIdentity::class,
        cascade: ['all'],
        orphanRemoval: true
    )]
    protected ?Collection $aclIdentities = null;

    /**
     * @var Collection<int, WorkflowRestrictionIdentity>
     */
    #[ORM\OneToMany(
        mappedBy: 'workflowItem',
        targetEntity: WorkflowRestrictionIdentity::class,
        cascade: ['all'],
        orphanRemoval: true
    )]
    protected ?Collection $restrictionIdentities = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $created = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updated = null;

    /**
     * Serialized data of WorkflowItem
     */
    #[ORM\Column(name: 'data', type: Types::TEXT, nullable: true)]
    protected ?string $serializedData = null;

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

    protected bool $locked = false;

    public function __construct()
    {
        $this->transitionRecords = new ArrayCollection();
        $this->aclIdentities = new ArrayCollection();
        $this->restrictionIdentities = new ArrayCollection();
        $this->data = new WorkflowData();
        $this->result = new WorkflowResult();
    }

    public function lock(): void
    {
        $this->locked = true;
    }

    public function unlock(): void
    {
        $this->locked = false;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @param WorkflowItem $source
     * @return $this
     */
    public function merge(WorkflowItem $source)
    {
        $this->getData()->add($source->getData()->toArray());
        $this->getResult()->add($source->getResult()->toArray());

        // Fill stub workflow item with actual data
        if (!$this->id && !$this->getEntityId()) {
            $this->id = $source->getId();
            $this->entity = $source->getEntity();
            $this->entityId = $source->getEntityId();
            $this->currentStep = $source->getCurrentStep();
            $this->updated = $source->getUpdated();
            $this->created = $source->getCreated();
        }

        return $this;
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
        if ($this->isLocked()) {
            throw new WorkflowException('Changing the step of a locked workflow item is prohibited.');
        }

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
     * This method should be called only from WorkflowItemListener.
     *
     * @param string|null $entityId
     * @return WorkflowItem
     * @throws WorkflowException
     */
    public function setEntityId(?string $entityId)
    {
        if ($this->entityId !== null && $this->entityId !== $entityId) {
            throw new WorkflowException('Workflow item entity ID can not be changed');
        }

        $this->entityId = $entityId;

        return $this;
    }

    /**
     * This method should be called only from WorkflowDataSerializeListener.
     *
     * @return string
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * This method should be called only from WorkflowItemListener.
     *
     * @param string|object $entityClass
     * @return WorkflowItem
     * @throws WorkflowException
     */
    public function setEntityClass($entityClass)
    {
        if (is_object($entityClass)) {
            $entityClass = ClassUtils::getClass($entityClass);
        }

        if ($this->entityClass !== null && $this->entityClass !== $entityClass) {
            throw new WorkflowException('Workflow item entity CLASS can not be changed');
        }

        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * This method should be called only from WorkflowDataSerializeListener.
     *
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
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
     * This method should be called only from WorkflowDataSerializeListener.
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
     * This method should be called only from WorkflowDataSerializeListener.
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

    #[\Override]
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
                    WorkflowData::class,
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
     * This method should be called only from WorkflowDataSerializeListener.
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
        $newAttributeSteps = [];
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
     * @return Collection|WorkflowRestrictionIdentity[]
     */
    public function getRestrictionIdentities()
    {
        return $this->restrictionIdentities;
    }

    public function addRestrictionIdentity(WorkflowRestrictionIdentity $restrictionIdentity)
    {
        $restrictionIdentity->setWorkflowItem($this);

        $this->restrictionIdentities->add($restrictionIdentity);
    }

    public function removeRestrictionIdentity(WorkflowRestrictionIdentity $restrictionIdentity)
    {
        $this->restrictionIdentities->removeElement($restrictionIdentity);
    }

    /**
     * @param Collection|WorkflowRestrictionIdentity[] $restrictionIdentities
     *
     * @return $this
     */
    public function setRestrictionIdentities($restrictionIdentities)
    {
        $newRestrictionsIdentities = [];
        foreach ($restrictionIdentities as $restrictionIdentity) {
            $newRestrictionsIdentities[$restrictionIdentity->getRestriction()->getHashKey()] = $restrictionIdentity;
        }

        $oldRestrictionsIdentities = $this->restrictionIdentities;
        foreach ($oldRestrictionsIdentities as $old) {
            $hashKey = $old->getRestriction()->getHashKey();
            if (isset($newRestrictionsIdentities[$hashKey])) {
                unset($newRestrictionsIdentities[$hashKey]);
            } else {
                $this->restrictionIdentities->removeElement($old);
            }
        }

        foreach ($newRestrictionsIdentities as $newRestrictionsIdentity) {
            $this->addRestrictionIdentity($newRestrictionsIdentity);
        }

        return $this;
    }

    /**
     * Get created date/time
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get last update date/time
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Pre persist event listener
     */
    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->created = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->setUpdated();
    }

    /**
     * Invoked before the entity is updated.
     */
    #[ORM\PreUpdate]
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

    /**
     * This method should be exists for compatibility with redirect action
     *
     * @param string $url
     * @return $this
     */
    public function setRedirectUrl($url)
    {
        $this->getResult()->set('redirectUrl', $url);

        return $this;
    }

    #[\Override]
    public function getActionDataStorage(): AbstractStorage
    {
        return $this->getData();
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return sprintf(
            '[%s] %s:%s %s',
            $this->workflowName,
            $this->entityClass,
            $this->entityId,
            $this->currentStep ? $this->currentStep->getName() : null
        );
    }
}
