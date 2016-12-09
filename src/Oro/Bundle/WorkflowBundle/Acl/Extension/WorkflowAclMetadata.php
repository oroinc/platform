<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Extension\AclClassInfo;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;

class WorkflowAclMetadata implements AclClassInfo, \Serializable
{
    /** @var string */
    protected $workflowName;

    /** @var string */
    protected $group;

    /** @var string */
    protected $label;

    /** @var string */
    protected $description;

    /** @var string */
    protected $category;

    /** @var FieldSecurityMetadata[] */
    protected $transitions;

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

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            $this->workflowName,
            $this->group,
            $this->label,
            $this->description,
            $this->category,
            $this->transitions
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->workflowName,
            $this->group,
            $this->label,
            $this->description,
            $this->category,
            $this->transitions
            ) = unserialize($serialized);
    }

    /**
     * The __set_state handler
     *
     * @param array $data Initialization array
     *
     * @return WorkflowAclMetadata A new instance of a WorkflowAclMetadata object
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        $result = new WorkflowAclMetadata();
        $result->workflowName = $data['workflowName'];
        $result->group = $data['group'];
        $result->label = $data['label'];
        $result->description = $data['description'];
        $result->category = $data['category'];
        $result->transitions = $data['transitions'];

        return $result;
    }
    // @codingStandardsIgnoreEnd
}
