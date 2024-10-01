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

    #[\Override]
    public function getIterator(): \Traversable
    {
    }

    #[\Override]
    public function offsetExists($offset): bool
    {
    }

    #[\Override]
    public function offsetGet($offset): mixed
    {
    }

    #[\Override]
    public function offsetSet($offset, $value): void
    {
    }

    #[\Override]
    public function offsetUnset($offset): void
    {
    }

    #[\Override]
    public function setFactory(FactoryInterface $factory): self
    {
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[\Override]
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    #[\Override]
    public function getUri(): ?string
    {
        return $this->uri;
    }

    #[\Override]
    public function setUri($uri): self
    {
        $this->uri = $uri;

        return $this;
    }

    #[\Override]
    public function getLabel(): string
    {
        return $this->label ?: $this->getName();
    }

    #[\Override]
    public function setLabel($label): self
    {
        $this->label = $label;

        return $this;
    }

    #[\Override]
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    #[\Override]
    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    #[\Override]
    public function getAttribute($name, $default = null)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        return $default;
    }

    #[\Override]
    public function setAttribute($name, $value): self
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    #[\Override]
    public function getLinkAttributes(): array
    {
    }

    #[\Override]
    public function setLinkAttributes(array $linkAttributes): self
    {
    }

    #[\Override]
    public function getLinkAttribute($name, $default = null)
    {
    }

    #[\Override]
    public function setLinkAttribute($name, $value): self
    {
    }

    #[\Override]
    public function getChildrenAttributes(): array
    {
    }

    #[\Override]
    public function setChildrenAttributes(array $childrenAttributes): self
    {
    }

    #[\Override]
    public function getChildrenAttribute($name, $default = null)
    {
    }

    #[\Override]
    public function setChildrenAttribute($name, $value): self
    {
    }

    #[\Override]
    public function getLabelAttributes(): array
    {
    }

    #[\Override]
    public function setLabelAttributes(array $labelAttributes): self
    {
    }

    #[\Override]
    public function getLabelAttribute($name, $default = null)
    {
    }

    #[\Override]
    public function setLabelAttribute($name, $value): self
    {
    }

    #[\Override]
    public function getExtras(): array
    {
        return $this->extras;
    }

    #[\Override]
    public function setExtras(array $extras): self
    {
        $this->extras = $extras;

        return $this;
    }

    #[\Override]
    public function getExtra($name, $default = null)
    {
        if (array_key_exists($name, $this->extras)) {
            return $this->extras[$name];
        }

        return $default;
    }

    #[\Override]
    public function setExtra($name, $value): self
    {
        $this->extras[$name] = $value;

        return $this;
    }

    #[\Override]
    public function getDisplayChildren(): bool
    {
        return true;
    }

    #[\Override]
    public function setDisplayChildren($bool): self
    {
    }

    #[\Override]
    public function isDisplayed(): bool
    {
        return $this->display;
    }

    #[\Override]
    public function setDisplay(bool $bool): ItemInterface
    {
        $this->display = $bool;

        return $this;
    }

    #[\Override]
    public function addChild($child, array $options = []): self
    {
        $this->children[] = $child;

        return $this;
    }

    #[\Override]
    public function getChild($name): ?self
    {
        if (array_key_exists($name, $this->children)) {
            return $this->children[$name];
        }

        return null;
    }

    #[\Override]
    public function reorderChildren($order): self
    {
    }

    #[\Override]
    public function copy(): self
    {
    }

    #[\Override]
    public function getLevel(): int
    {
    }

    #[\Override]
    public function getRoot(): self
    {
    }

    #[\Override]
    public function isRoot(): bool
    {
    }

    #[\Override]
    public function getParent(): ?self
    {
        return $this->parent;
    }

    #[\Override]
    public function setParent(ItemInterface $parent = null): self
    {
        $this->parent = $parent;

        return $this;
    }

    #[\Override]
    public function getChildren(): array
    {
        return $this->children;
    }

    #[\Override]
    public function setChildren(array $children): self
    {
        $this->children = $children;

        return $this;
    }

    #[\Override]
    public function removeChild($name): self
    {
        if (array_key_exists($name, $this->children)) {
            unset($this->children[$name]);
        }

        return $this;
    }

    #[\Override]
    public function getFirstChild(): self
    {
    }

    #[\Override]
    public function getLastChild(): self
    {
    }

    #[\Override]
    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    #[\Override]
    public function setCurrent($bool): self
    {
    }

    #[\Override]
    public function isCurrent(): bool
    {
    }

    #[\Override]
    public function isLast(): bool
    {
    }

    #[\Override]
    public function isFirst(): bool
    {
    }

    #[\Override]
    public function actsLikeFirst(): bool
    {
    }

    #[\Override]
    public function actsLikeLast(): bool
    {
    }

    #[\Override]
    public function count(): int
    {
        return count($this->children);
    }
}
