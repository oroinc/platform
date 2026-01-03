<?php

namespace Oro\Bundle\SecurityBundle\Attribute;

use Attribute;

/**
 * The attribute that defines ACL rule for a resource.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Acl
{
    private const DEFINED_PROPERTIES_LIST = [
        'id',
        'type',
        'ignoreClassAcl',
        'class',
        'permission',
        'groupName',
        'label',
        'description',
        'category',
        'validate',
    ];

    private ?string $id = null;
    private ?string $type = null;
    private ?bool $ignoreClassAcl = null;
    private ?string $class = null;
    private ?string $permission = null;
    private ?string $groupName = null;
    private ?string $label = null;
    private ?string $description = null;
    private ?string $category = null;
    private bool $validate = true;

    public function __construct(...$arguments)
    {
        foreach ($arguments as $propertyName => $propertyValue) {
            if (in_array($propertyName, self::DEFINED_PROPERTIES_LIST)) {
                $this->{$propertyName} = $propertyValue;
            }
        }

        if (false === $this->validate) {
            return;
        }

        if (empty($this->id) || str_contains($this->id, ' ')) {
            throw new \InvalidArgumentException('ACL id must not be empty or contain blank spaces.');
        }

        if (empty($this->type)) {
            throw new \InvalidArgumentException(sprintf('ACL type must not be empty. Id: %s.', $this->type));
        }

        $this->ignoreClassAcl ??= false;
        $this->permission ??= '';
        $this->class ??= '';
        $this->groupName ??= '';
        $this->label ??= '';
        $this->description ??= '';
        $this->category ??= '';
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function fromArray(?array $data = null)
    {
        return new static(
            id: $data['id'] ?? null,
            type: $data['type'] ?? null,
            ignoreClassAcl: $data['ignore_class_acl'] ?? null,
            class: $data['class'] ?? null,
            permission: $data['permission'] ?? null,
            groupName: $data['group_name'] ?? null,
            label: $data['label'] ?? null,
            description: $data['description'] ?? null,
            category: $data['category'] ?? null,
            validate: !is_null($data)
        );
    }

    /**
     * Gets ID of this ACL attribute.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Indicates whether a class level ACL attribute should be ignored or not.
     *
     * This property can be used in ACL attributes declared on method level only.
     * A default value for this property is false. It means that both method and
     * class level ACLs is checked to decide whether an access is granted or not.
     *
     * @return bool
     */
    public function getIgnoreClassAcl()
    {
        return $this->ignoreClassAcl;
    }

    /**
     * Gets ACL extension key.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets ACL class name.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Sets ACL class name.
     *
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * Gets ACL permission name.
     *
     * @return string
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * Sets ACL permission name.
     *
     * @param string $permission
     */
    public function setPermission($permission)
    {
        $this->permission = $permission;
    }

    /**
     * Gets ACL group name.
     *
     * @return string
     */
    public function getGroup()
    {
        return $this->groupName;
    }

    /**
     * Gets ACL label name.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Gets ACL description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Gets ACL category.
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Sets ACL category.
     *
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    public function __serialize(): array
    {
        return [
            $this->id,
            $this->type,
            $this->class,
            $this->permission,
            $this->ignoreClassAcl,
            $this->groupName,
            $this->label,
            $this->description,
            $this->category
        ];
    }

    public function __unserialize(array $serialized): void
    {
        [
            $this->id,
            $this->type,
            $this->class,
            $this->permission,
            $this->ignoreClassAcl,
            $this->groupName,
            $this->label,
            $this->description,
            $this->category
        ] = $serialized;
    }

    /**
     * The __set_state handler
     *
     * @param array $data Initialization array
     * @return Acl A new instance of a Acl object
     */
    // phpcs:disable
    public static function __set_state($data)
    {
        $result = new Acl(validate: false);
        $result->id = $data['id'];
        $result->type = $data['type'];
        $result->class = $data['class'];
        $result->permission = $data['permission'];
        $result->ignoreClassAcl = $data['ignoreClassAcl'];
        $result->groupName = $data['groupName'];
        $result->label = $data['label'];
        $result->description = $data['description'];
        $result->category = $data['category'];

        return $result;
    }
    // phpcs:enable
}
