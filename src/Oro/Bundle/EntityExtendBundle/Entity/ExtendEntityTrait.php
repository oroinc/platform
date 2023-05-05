<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Entity;

use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldProcessTransport;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendedEntityFieldsProcessor;
use Oro\Bundle\EntityExtendBundle\Model\ExtendEntityStorage;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityStaticCache;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * Extend entity functionality emulation trait.
 */
trait ExtendEntityTrait
{
    protected ?\ArrayObject $extendEntityStorage = null;

    public function getStorage(): \ArrayObject
    {
        if ($this->extendEntityStorage === null) {
            $this->extendEntityStorage = new ExtendEntityStorage(
                [],
                \ArrayObject::STD_PROP_LIST | \ArrayObject::ARRAY_AS_PROPS
            );
        }

        return $this->extendEntityStorage;
    }

    /**
     * @see \Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface::get()
     */
    public function get(string $name): mixed
    {
        if (ExtendEntityStaticCache::isAllowedIgnoreGet($this, $name) && $this->getStorage()->offsetExists($name)) {
            return $this->getStorage()[$name];
        }
        $transport = $this->createTransport();
        $transport->setName($name);

        ExtendedEntityFieldsProcessor::executeGet($transport);
        if ($transport->isProcessed()) {
            return $transport->getResult();
        }

        throw new \LogicException(
            sprintf('There is no extended property with the name %s in class: %s', $name, static::class)
        );
    }

    /**
     * @see \Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface::set()
     */
    public function set(string $name, mixed $value): static
    {
        if (!$this instanceof AbstractLocalizedFallbackValue
            && ExtendEntityStaticCache::isAllowedIgnoreSet($this, $name)) {
            $this->getStorage()->offsetSet($name, $value);

            return $this;
        }
        $transport = $this->createTransport();
        $transport->setName($name);
        $transport->setValue($value);

        ExtendedEntityFieldsProcessor::executeSet($transport);

        return $this;
    }

    private function createTransport(): EntityFieldProcessTransport
    {
        $transport = new EntityFieldProcessTransport();
        $transport->setObject($this);
        $transport->setStorage($this->getStorage());
        $transport->setObjectVars(\get_object_vars($this));

        return $transport;
    }

    public function __get(string $name)
    {
        if (ExtendEntityStaticCache::isAllowedIgnoreGet($this, $name) && $this->getStorage()->offsetExists($name)) {
            return $this->getStorage()[$name];
        }
        $transport = $this->createTransport();
        $transport->setName($name);

        ExtendedEntityFieldsProcessor::executeGet($transport);

        if ($transport->isProcessed()) {
            return $transport->getResult();
        }

        throw new \LogicException('Failed __get property access: ' . $name . ' Class: ' . static::class);
    }

    public function __set(string $name, $value)
    {
        if (!$this instanceof AbstractLocalizedFallbackValue
            && ExtendEntityStaticCache::isAllowedIgnoreSet($this, $name)) {
            $this->getStorage()->offsetSet($name, $value);

            return $this;
        }
        $transport = $this->createTransport();
        $transport->setName($name);
        $transport->setValue($value);

        ExtendedEntityFieldsProcessor::executeSet($transport);
        if (!$transport->isProcessed()) {
            throw new NoSuchPropertyException(
                sprintf(
                    'There is no "%s" property in "%s" entity',
                    $name,
                    static::class
                )
            );
        }
    }

    public function __isset(string $name)
    {
        $transport = $this->createTransport();
        $transport->setName($name);

        ExtendedEntityFieldsProcessor::executeIsset($transport);

        if ($transport->isProcessed()) {
            return (bool)$transport->getResult();
        }

        return false;
    }

    public function __call(string $name, array $arguments)
    {
        $transport = $this->createTransport();
        $transport->setName($name);
        $transport->setArguments($arguments);

        ExtendedEntityFieldsProcessor::executeCall($transport);

        if ($transport->isProcessed()) {
            foreach ($transport->getResultVars() as $fieldName => $value) {
                if (\property_exists($this, $fieldName)) {
                    $this->{$fieldName} = $value;
                } else {
                    $this->extendEntityStorage->offsetSet($fieldName, $value);
                }
            }

            return $transport->getResult();
        }
        throw new NoSuchPropertyException(
            sprintf(
                'There is no "%s" method in "%s" entity',
                $name,
                static::class
            )
        );
    }

    public function __clone()
    {
        if (is_callable('parent::__clone')) {
            parent::__clone();
        }
        $this->cloneExtendEntityStorage();
    }

    protected function cloneExtendEntityStorage(): void
    {
        if (null === $this->extendEntityStorage) {
            return;
        }
        $newStorage = new ExtendEntityStorage([], \ArrayObject::STD_PROP_LIST | \ArrayObject::ARRAY_AS_PROPS);
        foreach ($this->extendEntityStorage as $key => $value) {
            $newStorage->offsetSet($key, $value);
        }
        $this->extendEntityStorage = $newStorage;
    }

    protected function getExtendStorageFields(): array
    {
        if (null === $this->extendEntityStorage) {
            return [];
        }

        return $this->extendEntityStorage->getArrayCopy();
    }

    /**
     * This method needed only for phpunit EntityTrait.php
     */
    public function cleanExtendEntityStorage(): void
    {
        $this->extendEntityStorage = null;
    }
}
