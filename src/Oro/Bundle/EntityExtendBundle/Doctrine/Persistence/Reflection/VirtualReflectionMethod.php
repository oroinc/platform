<?php

namespace Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Reflection;

use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;

/**
 * Emulates extend entity virtual methods as ReflectionMethod
 */
class VirtualReflectionMethod extends \ReflectionMethod
{
    protected const DONOR_METHOD_NAME = 'get';
    private string $virtualMethod;
    private bool $isRealMethod = false;

    /**
     * When method belongs to a parent class, $this->class can't be used to know the original object class name
     */
    private string $objectClass;

    public function __construct(object|string $objectOrMethod, ?string $method = null)
    {
        if (null === $method) {
            throw new \RuntimeException('Virtual reflection method is not implemented');
        }
        try {
            parent::__construct($objectOrMethod, $method);
            $this->isRealMethod = true;
        } catch (\ReflectionException $exception) {
            // If it is not possible to create a reflection method, we try to create a virtual method
            $this->virtualMethod = $method;
            parent::__construct($objectOrMethod, static::DONOR_METHOD_NAME);
            $this->objectClass = is_string($objectOrMethod) ? $objectOrMethod : $objectOrMethod::class;
        }
    }

    public static function create(object|string $objectOrMethod, string $method): self
    {
        return new static($objectOrMethod, $method);
    }

    public function getName(): string
    {
        if ($this->isRealMethod) {
            return parent::getName();
        }

        return $this->virtualMethod;
    }

    public function isPublic(): bool
    {
        if ($this->isRealMethod) {
            return parent::isPublic();
        }

        return true;
    }

    public function getNumberOfRequiredParameters(): int
    {
        if ($this->isRealMethod) {
            return parent::getNumberOfRequiredParameters();
        }

        $isGetMethod = str_starts_with($this->virtualMethod, 'get');
        if ($isGetMethod) {
            return 0;
        }
        if (str_starts_with($this->virtualMethod, 'remove') || str_starts_with($this->virtualMethod, 'add')) {
            return 1;
        }
        $methodInfo = EntityPropertyInfo::getExtendedMethodInfo($this->objectClass, $this->virtualMethod);
        if (empty($methodInfo)) {
            return 1;
        }

        return $methodInfo['is_nullable'] ? 0 : 1;
    }

    public function invoke($object, mixed ...$args): mixed
    {
        return $this->invokeArgs($object, $args);
    }

    public function invokeArgs(?object $object, array $args): mixed
    {
        if ($this->isRealMethod) {
            return parent::invokeArgs($object, $args);
        }

        return $object->{$this->getName()}(...$args);
    }
}
