<?php

/*
 * This file is a copy of {@see Nelmio\Alice\FixtureBuilder\ExpressionLanguage\Lexer\SubPatternsLexer}
 *
 * (c) Nelmio <hello@nelm.io>
 */

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;

/**
 * All methods except #find and #random are copied from {@see \Doctrine\Common\Collections\ArrayCollection},
 * to avoid a hard dependency. See ArrayCollection for attribution.
 */
class Collection extends ArrayCollection
{
    /**
     * An array containing the entries of this collection.
     *
     * @var object[]
     */
    private $instances;

    /**
     * Instance keys that match random mask
     * [
     *      mask => [keys matching mask],
     *      mask2 => [keys matching mask2],
     *      ...
     * ]
     *
     * @var array
     */
    private $keysByMask = [];

    /**
     * Initializes a new ArrayCollection.
     *
     * @param object[] $elements
     */
    public function __construct(array $elements = [])
    {
        $this->instances = $elements;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->instances;
    }

    /**
     * {@inheritDoc}
     */
    public function containsKey($name)
    {
        return isset($this->instances[$name]) || array_key_exists($name, $this->instances);
    }

    /**
     * {@inheritDoc}
     */
    public function contains($element)
    {
        return in_array($element, $this->instances, true);
    }

    /**
     * {@inheritDoc}
     */
    public function get($name)
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function set($name, $instance)
    {
        $this->instances[$name] = $instance;
    }

    /**
     * {@inheritDoc}
     */
    public function remove($name)
    {
        if (isset($this->instances[$name]) || array_key_exists($name, $this->instances)) {
            $removed = $this->instances[$name];
            unset($this->instances[$name]);

            return $removed;
        }

        return null;
    }

    /**
     * Returns an object, or a property on that object if $property is not null.
     *
     * @param string      $name
     * @param string|null $property
     *
     * @throws \UnexpectedValueException
     *
     * @return object
     */
    public function find($name, $property = null)
    {
        if (false === $this->containsKey($name)) {
            throw new \UnexpectedValueException(
                sprintf('Instance %s is not defined', $name)
            );
        }
        $object = $this->get($name);

        if (null === $property) {
            return $object;
        }

        if (property_exists($object, $property)) {
            $prop = new \ReflectionProperty($object, $property);

            if ($prop->isPublic()) {
                return $object->{$property};
            }
        }

        $getter = 'get'.ucfirst($property);
        if (EntityPropertyInfo::methodExists($object, $getter) && is_callable([$object, $getter])) {
            return $object->$getter();
        }

        throw new \UnexpectedValueException(
            sprintf('Property %s is not defined for instance %s', $property, $name)
        );
    }

    /**
     * Gets instance keys that match given mask.
     *
     * @param string $mask
     *
     * @return string[]
     */
    public function getKeysByMask($mask)
    {
        if (!isset($this->keysByMask[$mask])) {
            $this->keysByMask[$mask] = array_values(
                preg_grep(
                    sprintf(
                        '/^%s$/',
                        str_replace('\\*', '.+', preg_quote($mask))
                    ),
                    array_keys($this->instances)
                )
            );
        }

        return $this->keysByMask[$mask];
    }

    /**
     * Clears the collection, removing all elements.
     */
    public function clear()
    {
        $this->instances = [];
        $this->keysByMask = [];
    }
}
