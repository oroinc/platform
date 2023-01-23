<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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

    /** @var bool */
    protected $display = true;

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): mixed
    {
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setFactory(FactoryInterface $factory): self
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * {@inheritdoc}
     */
    public function setUri($uri): self
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return $this->label ?: $this->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function setLabel($label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttributes(array $attributes): self
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
    public function setAttribute($name, $value): self
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLinkAttributes(): array
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setLinkAttributes(array $linkAttributes): self
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
    public function setLinkAttribute($name, $value): self
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getChildrenAttributes(): array
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setChildrenAttributes(array $childrenAttributes): self
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
    public function setChildrenAttribute($name, $value): self
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getLabelAttributes(): array
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setLabelAttributes(array $labelAttributes): self
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
    public function setLabelAttribute($name, $value): self
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getExtras(): array
    {
        return $this->extras;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtras(array $extras): self
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
    public function setExtra($name, $value): self
    {
        $this->extras[$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayChildren(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setDisplayChildren($bool): self
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isDisplayed(): bool
    {
        return $this->display;
    }

    /**
     * {@inheritdoc}
     */
    public function setDisplay(bool $bool): ItemInterface
    {
        $this->display = $bool;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addChild($child, array $options = []): self
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getChild($name): ?self
    {
        if (array_key_exists($name, $this->children)) {
            return $this->children[$name];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function reorderChildren($order): self
    {
    }

    /**
     * {@inheritdoc}
     */
    public function copy(): self
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getLevel(): int
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getRoot(): self
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isRoot(): bool
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function setParent(ItemInterface $parent = null): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * {@inheritdoc}
     */
    public function setChildren(array $children): self
    {
        $this->children = $children;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeChild($name): self
    {
        if (array_key_exists($name, $this->children)) {
            unset($this->children[$name]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstChild(): self
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getLastChild(): self
    {
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrent($bool): self
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isCurrent(): bool
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isLast(): bool
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isFirst(): bool
    {
    }

    /**
     * {@inheritdoc}
     */
    public function actsLikeFirst(): bool
    {
    }

    /**
     * {@inheritdoc}
     */
    public function actsLikeLast(): bool
    {
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->children);
    }
}
