<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Reflection;

use Doctrine\Common\Proxy\Proxy;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;

/**
 * Emulates virtual property as ReflectionProperty
 */
class ReflectionVirtualProperty extends \ReflectionProperty
{
    protected static ?\stdClass $propertyDonor = null;

    private function __construct($class, $property)
    {
        parent::__construct($class, $property);
    }

    public static function create($property): self
    {
        if (self::$propertyDonor === null) {
            self::$propertyDonor = new \stdClass();
        }

        self::$propertyDonor->{$property} = null;

        return new static(self::$propertyDonor, $property);
    }

    /**
     * @param object|ExtendEntityInterface|null $object
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function getValue(?object $object = null): mixed
    {
        if ($object instanceof Proxy && !$object->__isInitialized()) {
            $originalInitializer = $object->__getInitializer();
            $object->__setInitializer(null);
            $val = $object->get($this->name);
            $object->__setInitializer($originalInitializer);

            return $val;
        }

        return $object->get($this->name);
    }

    /**
     * @param mixed|ExtendEntityInterface $object
     * @param mixed|null $value
     * @return void
     */
    #[ReturnTypeWillChange]
    public function setValue(mixed $object, mixed $value = null): void
    {
        if (!($object instanceof Proxy && !$object->__isInitialized())) {
            $object->set($this->name, $value);
            return;
        }

        $originalInitializer = $object->__getInitializer();
        $object->__setInitializer(null);
        $object->set($this->name, $value);
        $object->__setInitializer($originalInitializer);
    }
}
