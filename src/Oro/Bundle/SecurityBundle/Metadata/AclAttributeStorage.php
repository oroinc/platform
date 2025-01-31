<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Oro\Bundle\SecurityBundle\Attribute\Acl as AclAttribute;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor as AclAttributeAncestor;

/**
 * The storage for ACL attributes and bindings.
 */
class AclAttributeStorage
{
    /** @var AclAttribute[] [attribute id => attribute object, ...] */
    private $attributes = [];

    /** @var array [class name => [method name ('!' for class if it have an attribute) => attribute id, ...], ...] */
    private $classes = [];

    /**
     * Gets an attribute by its id
     *
     * @param  string                    $id
     * @return AclAttribute|null        AclAttribute object or null if ACL attribute was not found
     * @throws \InvalidArgumentException
     */
    public function findById($id)
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('$id must not be empty.');
        }

        return $this->attributes[$id] ?? null;
    }

    /**
     * Gets an attribute bound to the given class/method
     *
     * @param  string                    $class
     * @param  string|null               $method
     * @return AclAttribute|null        AclAttribute object or null if ACL attribute was not found
     * @throws \InvalidArgumentException
     */
    public function find($class, $method = null)
    {
        if (empty($class)) {
            throw new \InvalidArgumentException('$class must not be empty.');
        }

        if (empty($method)) {
            if (!isset($this->classes[$class]['!'])) {
                return null;
            }
            $id = $this->classes[$class]['!'];
        } else {
            if (!isset($this->classes[$class][$method])) {
                return null;
            }
            $id = $this->classes[$class][$method];
        }

        return $this->attributes[$id] ?? null;
    }

    /**
     * Determines whether the given class/method has an attribute
     *
     * @param  string      $class
     * @param  string|null $method
     * @return bool
     */
    public function has($class, $method = null)
    {
        if (empty($method)) {
            if (!isset($this->classes[$class]['!'])) {
                return false;
            }
            $id = $this->classes[$class]['!'];
        } else {
            if (!isset($this->classes[$class][$method])) {
                return false;
            }
            $id = $this->classes[$class][$method];
        }

        return isset($this->attributes[$id]);
    }

    /**
     * Gets attributes
     *
     * @param  string|null     $type The attribute type
     * @return AclAttribute[]
     */
    public function getAttributes($type = null)
    {
        if ($type === null) {
            return \array_values($this->attributes);
        }

        $result = [];
        foreach ($this->attributes as $attribute) {
            if ($attribute->getType() === $type) {
                $result[] = $attribute;
            }
        }

        return $result;
    }

    /**
     * Checks whether the given class is registered in this storage
     *
     * @param  string $class
     * @return bool   true if the class is registered in this storage; otherwise, false
     */
    public function isKnownClass($class)
    {
        return isset($this->classes[$class]);
    }

    /**
     * Checks whether the given method is registered in this storage
     *
     * @param  string $class
     * @param  string $method
     * @return bool   true if the method is registered in this storage; otherwise, false
     */
    public function isKnownMethod($class, $method)
    {
        return isset($this->classes[$class][$method]);
    }

    /**
     * Adds an attribute
     *
     * @param  AclAttribute             $attribute
     * @param  string|null               $class
     * @param  string|null               $method
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function add(AclAttribute $attribute, $class = null, $method = null)
    {
        $id = $attribute->getId();
        $this->attributes[$id] = $attribute;
        if ($class !== null) {
            $this->addBinding($id, $class, $method);
        }
    }

    /**
     * Adds an attribute ancestor
     *
     * @param  AclAttributeAncestor     $ancestor
     * @param  string|null               $class
     * @param  string|null               $method
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function addAncestor(AclAttributeAncestor $ancestor, $class = null, $method = null)
    {
        if ($class !== null) {
            $this->addBinding($ancestor->getId(), $class, $method);
        }
    }

    /**
     * Gets bindings for class.
     *
     * @param string $class
     *
     * @return array [method name => attribute, ...]
     */
    public function getBindings(string $class): array
    {
        return $this->classes[$class] ?? [];
    }

    /**
     * Removes bindings for class.
     */
    public function removeBindings(string $class): void
    {
        unset($this->classes[$class]);
    }

    /**
     * Removes an attribute binding.
     */
    public function removeBinding(string $class, ?string $method = null): void
    {
        if (empty($class)) {
            throw new \InvalidArgumentException('$class must not be empty.');
        }

        if (empty($method)) {
            $method = '!';
        }

        if (isset($this->classes[$class][$method])) {
            unset($this->classes[$class][$method]);
        }
    }

    /**
     * Adds an attribute binding
     *
     * @param  string                    $id
     * @param  string                    $class
     * @param  string|null               $method
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function addBinding($id, $class, $method = null)
    {
        if (empty($class)) {
            throw new \InvalidArgumentException('$class must not be empty.');
        }

        if (isset($this->classes[$class])) {
            if (empty($method)) {
                if (isset($this->classes[$class]['!']) && $this->classes[$class]['!'] !== $id) {
                    throw new \RuntimeException(\sprintf(
                        'Duplicate binding for "%s". New Id: %s. Existing Id: %s',
                        $class,
                        $id,
                        $this->classes[$class]['!']
                    ));
                }
                $this->classes[$class]['!'] = $id;
            } else {
                if (isset($this->classes[$class][$method]) && $this->classes[$class][$method] !== $id) {
                    throw new \RuntimeException(\sprintf(
                        'Duplicate binding for "%s". New Id: %s. Existing Id: %s',
                        $class . '::' . $method,
                        $id,
                        $this->classes[$class][$method]
                    ));
                }
                $this->classes[$class][$method] = $id;
            }
        } elseif (empty($method)) {
            $this->classes[$class] = ['!' => $id];
        } else {
            $this->classes[$class] = [$method => $id];
        }
    }

    public function __serialize(): array
    {
        $data = [];
        foreach ($this->attributes as $attribute) {
            $data[] = $attribute->__serialize();
        }

        return [$data, $this->classes];
    }

    public function __unserialize(array $serialized): void
    {
        [$data, $this->classes] = $serialized;

        $this->attributes = [];
        foreach ($data as $d) {
            $attribute = AclAttribute::fromArray();
            $attribute->__unserialize($d);
            $this->attributes[$attribute->getId()] = $attribute;
        }
    }

    /**
     * The __set_state handler
     *
     * @param array $data Initialization array
     * @return AclAttributeStorage A new instance of a AclAttributeStorage object
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        $result = new AclAttributeStorage();
        $result->attributes = $data['attributes'];
        $result->classes = $data['classes'];

        return $result;
    }
    // @codingStandardsIgnoreEnd
}
