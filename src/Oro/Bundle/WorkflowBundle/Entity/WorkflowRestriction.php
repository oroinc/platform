<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowRestrictionRepository;

/**
 * Represents an entity restriction used in workflows.
 */
#[ORM\Entity(repositoryClass: WorkflowRestrictionRepository::class)]
#[ORM\Table(name: 'oro_workflow_restriction')]
#[ORM\UniqueConstraint(
    name: 'oro_workflow_restriction_idx',
    columns: ['workflow_name', 'workflow_step_id', 'field', 'entity_class', 'mode']
)]
class WorkflowRestriction
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WorkflowDefinition::class, inversedBy: 'restrictions')]
    #[ORM\JoinColumn(name: 'workflow_name', referencedColumnName: 'name', nullable: false, onDelete: 'CASCADE')]
    protected ?WorkflowDefinition $definition = null;

    #[ORM\Column(name: 'field', type: Types::STRING, length: 150, nullable: false)]
    protected ?string $field = null;

    #[ORM\ManyToOne(targetEntity: WorkflowStep::class)]
    #[ORM\JoinColumn(name: 'workflow_step_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?WorkflowStep $step = null;

    #[ORM\Column(name: 'attribute', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $attribute = null;

    #[ORM\Column(name: 'entity_class', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $entityClass = null;

    #[ORM\Column(name: 'mode', type: Types::STRING, length: 8)]
    protected ?string $mode = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'mode_values', type: Types::JSON, nullable: true)]
    protected $values = [];

    /**
     * @var Collection<int, WorkflowRestrictionIdentity>
     */
    #[ORM\OneToMany(
        mappedBy: 'restriction',
        targetEntity: WorkflowRestrictionIdentity::class,
        cascade: ['all'],
        orphanRemoval: true
    )]
    protected ?Collection $restrictionIdentities = null;

    /**
     * @return int
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
     * @return string
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @param string $attribute
     *
     * @return $this
     */
    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;

        return $this;
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
            $this->getAttribute(),
            $this->getMode()
        ];

        return md5(json_encode($hashParams));
    }

    /**
     * @return Collection|WorkflowRestrictionIdentity[]
     */
    public function getRestrictionIdentities()
    {
        return $this->restrictionIdentities;
    }

    /**
     * @param Collection|WorkflowRestrictionIdentity[] $restrictionIdentities
     */
    public function setRestrictionIdentities($restrictionIdentities)
    {
        $this->restrictionIdentities = $restrictionIdentities;
    }

    /**
     * @param WorkflowRestriction $restriction
     *
     * @return WorkflowEntityAcl
     */
    public function import(WorkflowRestriction $restriction)
    {
        $this
            ->setField($restriction->getField())
            ->setAttribute($restriction->getAttribute())
            ->setEntityClass($restriction->getEntityClass())
            ->setMode($restriction->getMode())
            ->setValues($restriction->getValues());

        if (null !== $restriction->getStep()) {
            $this->setStep(
                $this->getDefinition()->getStepByName($restriction->getStep()->getName())
            );
        }

        return $this;
    }
}
