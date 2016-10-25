<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class MenuItemStub implements ItemInterface
{
    /** @var string */
    private $name;

    /** @var string */
    private $label;

    /** @var string */
    private $uri;

    /** @var array */
    private $attributes = [];

    /** @var array */
    private $extras = [];

    /** @var ItemInterface[] */
    private $children = [];

    /** @var ItemInterface */
    private $parent = null;

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setFactory(FactoryInterface $factory)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * {@inheritdoc}
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label ? $this->label : $this->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($name, $default = null)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLinkAttributes()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setLinkAttributes(array $linkAttributes)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getLinkAttribute($name, $default = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setLinkAttribute($name, $value)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getChildrenAttributes()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setChildrenAttributes(array $childrenAttributes)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getChildrenAttribute($name, $default = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setChildrenAttribute($name, $value)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getLabelAttributes()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setLabelAttributes(array $labelAttributes)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getLabelAttribute($name, $default = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setLabelAttribute($name, $value)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtras(array $extras)
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtra($name, $default = null)
    {
        if (array_key_exists($name, $this->extras)) {
            return $this->extras[$name];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtra($name, $value)
    {
        $this->extras[$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayChildren()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setDisplayChildren($bool)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isDisplayed()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setDisplay($bool)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function addChild($child, array $options = [])
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getChild($name)
    {
        if (array_key_exists($name, $this->children)) {
            return $this->children[$name];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function reorderChildren($order)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function copy()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getLevel()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getRoot()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isRoot()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function setParent(ItemInterface $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * {@inheritdoc}
     */
    public function setChildren(array $children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeChild($name)
    {
        if (array_key_exists($name, $this->children)) {
            unset($this->children[$name]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstChild()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getLastChild()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren()
    {
        return !empty($this->children);
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrent($bool)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isCurrent()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isLast()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isFirst()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function actsLikeFirst()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function actsLikeLast()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
    }
}
