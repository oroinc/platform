<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\EntityExtend;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

/**
 * Universal Transport for Entity Field Processing
 */
class EntityFieldProcessTransport
{
    public const EXISTS_PROPERTY = 'property';
    public const EXISTS_METHOD = 'method';

    private ?object $object = null;
    private string $class = '';
    private array $objectVars = [];
    private ?\ArrayAccess $storage = null;
    private string $name = '';
    private ?ExtendEntityMetadataProviderInterface $metadataProvider = null;
    private mixed $value = null;
    private array $arguments = [];
    private mixed $result = null;
    private array $resultVars = [];
    private bool $processed = false;

    public function getObject(): ?object
    {
        return $this->object;
    }

    public function setObject(object $object): static
    {
        $this->object = $object;

        return $this;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setClass(string $class): void
    {
        $this->class = CachedClassUtils::getRealClass($class);
    }

    public function getStorage(): \ArrayAccess
    {
        return $this->storage;
    }

    public function setStorage(\ArrayAccess $storage): static
    {
        $this->storage = $storage;

        return $this;
    }

    /**
     * @return array
     */
    public function getFieldsMetadata(): array
    {
        return $this->metadataProvider->getExtendEntityFieldsMetadata($this->class);
    }

    public function getEntityMetadata(): ConfigInterface
    {
        return $this->metadataProvider->getExtendEntityMetadata($this->class);
    }

    public function setEntityMetadataProvider(ExtendEntityMetadataProviderInterface $entityFieldIterator): static
    {
        $this->metadataProvider = $entityFieldIterator;

        return $this;
    }

    public function getObjectVar(string $name): mixed
    {
        return $this->objectVars[$name] ?? null;
    }

    public function setObjectVars(array $objectVars): static
    {
        $this->objectVars = $objectVars;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgument(int $index): mixed
    {
        return $this->arguments[$index] ?? null;
    }

    public function setArguments(array $arguments): static
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function setResult(mixed $result): static
    {
        $this->result = $result;

        return $this;
    }

    public function getResultVars(): array
    {
        return $this->resultVars;
    }

    public function addResultVar(string $name, mixed $value): static
    {
        $this->resultVars[$name] = $value;

        return $this;
    }

    public function isProcessed(): bool
    {
        return $this->processed;
    }

    public function setProcessed(bool $processed): static
    {
        $this->processed = $processed;

        return $this;
    }
}
