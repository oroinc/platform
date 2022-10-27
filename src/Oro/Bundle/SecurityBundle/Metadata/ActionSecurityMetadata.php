<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

/**
 * Represents security metadata for an action (another name is capability).
 */
class ActionSecurityMetadata implements ClassSecurityMetadata
{
    /** @var string */
    private $name;

    /** @var string */
    private $group;

    /** @var string */
    private $label;

    /** @var string */
    private $description;

    /** @var string */
    private $category;

    /**
     * @param string $name
     * @param string $group
     * @param string $label
     * @param string $description
     * @param string $category
     */
    public function __construct($name = '', $group = '', $label = '', $description = '', $category = '')
    {
        $this->name = $name;
        $this->group = $group;
        $this->label = $label;
        $this->description = $description;
        $this->category = $category;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName()
    {
        return $this->name;
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
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return [];
    }

    public function __serialize(): array
    {
        return [
            $this->name,
            $this->group,
            $this->label,
            $this->description,
            $this->category
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function __unserialize(array $serialized): void
    {
        [
            $this->name,
            $this->group,
            $this->label,
            $this->description,
            $this->category
        ] = $serialized;
    }

    /**
     * @param array $data
     *
     * @return ActionSecurityMetadata
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        return new ActionSecurityMetadata(
            $data['name'],
            $data['group'],
            $data['label'],
            $data['description'],
            $data['category']
        );
    }
    // @codingStandardsIgnoreEnd
}
