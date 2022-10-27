<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Metadata\ClassSecurityMetadata;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;

/**
 * Represents security metadata for a workflow.
 */
class WorkflowAclMetadata implements ClassSecurityMetadata
{
    /** @var string */
    private $workflowName;

    /** @var string */
    private $group;

    /** @var string */
    private $label;

    /** @var string */
    private $description;

    /** @var string */
    private $category;

    /** @var FieldSecurityMetadata[] */
    private $transitions;

    /**
     * @param string                  $workflowName
     * @param string                  $label
     * @param string                  $description
     * @param FieldSecurityMetadata[] $transitions
     * @param string                  $group
     * @param string                  $category
     */
    public function __construct(
        $workflowName = '',
        $label = '',
        $description = '',
        $transitions = [],
        $group = '',
        $category = ''
    ) {
        $this->workflowName = $workflowName;
        $this->label = $label;
        $this->description = $description;
        $this->transitions = $transitions;
        $this->group = $group;
        $this->category = $category;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName()
    {
        return $this->workflowName;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return $this->transitions;
    }

    public function __serialize(): array
    {
        return [
            $this->workflowName,
            $this->group,
            $this->label,
            $this->description,
            $this->category,
            $this->transitions
        ];
    }

    public function __unserialize(array $serialized): void
    {
        [
            $this->workflowName,
            $this->group,
            $this->label,
            $this->description,
            $this->category,
            $this->transitions
        ] = $serialized;
    }

    /**
     * @param array $data
     *
     * @return WorkflowAclMetadata
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        return new WorkflowAclMetadata(
            $data['workflowName'],
            $data['label'],
            $data['description'],
            $data['transitions'],
            $data['group'],
            $data['category']
        );
    }
    // @codingStandardsIgnoreEnd
}
