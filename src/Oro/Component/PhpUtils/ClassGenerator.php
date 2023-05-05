<?php
declare(strict_types=1);

namespace Oro\Component\PhpUtils;

use Nette\InvalidStateException;
use Nette\PhpGenerator\Attribute;
use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Constant;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;
use Nette\PhpGenerator\PsrPrinter;

/**
 * Namespace-aware wrapper over \Nette\ClassType
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
final class ClassGenerator
{
    private ?PhpNamespace $namespace = null;
    private ClassType $classType;
    private PsrPrinter $printer;

    public function __construct(?string $classname = null)
    {
        if (null !== $classname) {
            $pos = \strrpos($classname, '\\');
            if (false !== $pos) {
                $this->namespace = new PhpNamespace(\substr($classname, 0, $pos));
                $this->classType = $this->namespace->addClass(\substr($classname, $pos + 1, \strlen($classname)));
            } else {
                $this->classType = new ClassType($classname);
            }
        } else {
            $this->classType = new ClassType($classname);
        }
        $this->printer = new PsrPrinter();
    }

    public function print(bool $skipNamespace = false): string
    {
        return $this->namespace && !$skipNamespace
            ? $this->printer->printNamespace($this->namespace)
            : $this->printer->printClass($this->classType, $this->namespace);
    }

    public function addUse(string $name, string $alias = null, string &$aliasOut = null): self
    {
        $aliasOut = !empty($aliasOut) ? $aliasOut : PhpNamespace::NAME_NORMAL;

        if (null === $this->namespace) {
            throw new InvalidStateException('Cannot add imports to a non-namespaced class.');
        }
        $this->namespace->addUse($name, $alias, $aliasOut);
        return $this;
    }

    public function __clone()
    {
        $this->classType = clone $this->classType;
        if (null !== $this->namespace) {
            $this->namespace = clone $this->namespace;
            $this->add($this->classType);
        }

        // It is not cloned intentionally. Uncomment if run into side-effects.
        // $this->printer = clone $this->printer;
    }

    //region Proxying most used public methods of \Nette\ClassType
    /** @return static */
    public function addAttribute(string $name, array $args = []): self
    {
        $this->classType->addAttribute($name, $args);
        return $this;
    }

    /**
     * @param Attribute[] $attrs
     * @return static
     */
    public function setAttributes(array $attrs): self
    {
        $this->classType->setAttributes($attrs);
        return $this;
    }

    /** @return Attribute[] */
    public function getAttributes(): array
    {
        return $this->classType->getAttributes();
    }

    /**
     * Returns the generated class code.
     */
    public function __toString(): string
    {
        return $this->print();
    }

    public function getNamespace(): ?PhpNamespace
    {
        return $this->namespace;
    }

    /** @return static */
    public function setName(?string $name): self
    {
        $this->classType->setName($name);
        return $this;
    }

    public function getName(): ?string
    {
        return $this->classType->getName();
    }

    /** @return static */
    public function setClass(): self
    {
        $this->classType->setClass();
        return $this;
    }

    public function isClass(): bool
    {
        return $this->classType->isClass();
    }

    /** @return static */
    public function setInterface(): self
    {
        $this->classType->setInterface();
        return $this;
    }

    public function isInterface(): bool
    {
        return $this->classType->isInterface();
    }

    /** @return static */
    public function setTrait(): self
    {
        $this->classType->setTrait();
        return $this;
    }

    public function isTrait(): bool
    {
        return $this->classType->isTrait();
    }

    /** @return static */
    public function setType(string $type): self
    {
        $this->classType->setType($type);
        return $this;
    }

    public function getType(): string
    {
        return $this->classType->getType();
    }

    /** @return static */
    public function setFinal(bool $state = true): self
    {
        $this->classType->setFinal($state);
        return $this;
    }

    public function isFinal(): bool
    {
        return $this->classType->isFinal();
    }

    /** @return static */
    public function setAbstract(bool $state = true): self
    {
        $this->classType->setAbstract($state);
        return $this;
    }

    public function isAbstract(): bool
    {
        return $this->classType->isAbstract();
    }

    /**
     * @param string|string[] $names
     * @return static
     */
    public function setExtends($names): self
    {
        $this->classType->setExtends($names);
        return $this;
    }

    /** @return string|string[] */
    public function getExtends()
    {
        return $this->classType->getExtends();
    }

    /** @return static */
    public function addExtend(string $name): self
    {
        $this->classType->setExtends($name);
        return $this;
    }

    /**
     * @param string[] $names
     * @return static
     */
    public function setImplements(array $names): self
    {
        $this->classType->setImplements($names);
        return $this;
    }

    /** @return string[] */
    public function getImplements(): array
    {
        return $this->classType->getImplements();
    }

    /** @return static */
    public function addImplement(string $name): self
    {
        $this->classType->addImplement($name);
        return $this;
    }

    /** @return static */
    public function removeImplement(string $name): self
    {
        $this->classType->removeImplement($name);
        return $this;
    }

    /**
     * @param string[] $names
     * @return static
     */
    public function setTraits(array $names): self
    {
        $this->classType->setTraits($names);
        return $this;
    }

    /** @return string[] */
    public function getTraits(): array
    {
        return $this->classType->getTraits();
    }

    /** @internal */
    public function getTraitResolutions(): array
    {
        return $this->classType->getTraitResolutions();
    }

    /** @return static */
    public function addTrait(string $name, array $resolutions = []): self
    {
        $this->classType->addTrait($name, $resolutions);
        return $this;
    }

    /** @return static */
    public function removeTrait(string $name): self
    {
        $this->classType->removeTrait($name);
        return $this;
    }

    /**
     * @param Method|Property|Constant $member
     * @return static
     */
    public function addMember($member): self
    {
        $this->classType->addMember($member);
        return $this;
    }

    /**
     * @param Constant[]|mixed[] $consts
     * @return static
     */
    public function setConstants(array $consts): self
    {
        $this->classType->setConstants($consts);
        return $this;
    }

    /** @return Constant[] */
    public function getConstants(): array
    {
        return $this->classType->getConstants();
    }

    public function addConstant(string $name, $value): Constant
    {
        return $this->classType->addConstant($name, $value);
    }

    /** @return static */
    public function removeConstant(string $name): self
    {
        $this->classType->removeConstant($name);
        return $this;
    }

    /**
     * @param Property[] $props
     * @return static
     */
    public function setProperties(array $props): self
    {
        $this->classType->setProperties($props);
        return $this;
    }

    /** @return Property[] */
    public function getProperties(): array
    {
        return $this->classType->getProperties();
    }

    public function getProperty(string $name): Property
    {
        return $this->classType->getProperty($name);
    }

    /**
     * @param string $name without $
     */
    public function addProperty(string $name, $value = null): Property
    {
        return $this->classType->addProperty($name, $value);
    }

    /**
     * @param string $name without $
     * @return static
     */
    public function removeProperty(string $name): self
    {
        $this->classType->removeProperty($name);
        return $this;
    }

    public function hasProperty(string $name): bool
    {
        return $this->classType->hasProperty($name);
    }

    /**
     * @param Method[] $methods
     * @return static
     */
    public function setMethods(array $methods): self
    {
        $this->classType->setMethods($methods);
        return $this;
    }

    /** @return Method[] */
    public function getMethods(): array
    {
        return $this->classType->getMethods();
    }

    public function getMethod(string $name): Method
    {
        return $this->classType->getMethod($name);
    }

    public function addMethod(string $name): Method
    {
        if ($this->classType->hasMethod($name)) {
            $this->classType->removeMethod($name);
        }

        return $this->classType->addMethod($name);
    }

    /** @return static */
    public function removeMethod(string $name): self
    {
        $this->classType->removeMethod($name);
        return $this;
    }

    public function hasMethod(string $name): bool
    {
        return $this->classType->hasMethod($name);
    }

    /** @throws \Nette\InvalidStateException */
    public function validate(): void
    {
        $this->classType->validate();
    }

    /** @return static */
    public function setComment(?string $val): self
    {
        $this->classType->setComment($val);
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->classType->getComment();
    }

    /** @return static */
    public function addComment(string $val): self
    {
        $this->classType->addComment($val);
        return $this;
    }

    //endregion

    public function add(ClassLike $class): PhpNamespace
    {
        if (in_array($class, $this->namespace->getClasses())) {
            $this->namespace->removeClass($class->getName());
        }

        return $this->namespace->add($class);
    }
}
