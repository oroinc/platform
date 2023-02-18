<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;

/**
 * The accessor to a data of a building document.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DocumentBuilderDataAccessor implements DataAccessorInterface
{
    private ?int $rootIndex = null;
    private int $lastIndex = -1;
    /** @var array [entity data or [common data, data, name, collection index] for association, ...] */
    private array $stack = [];
    private ?string $path = null;
    /** @var array [data item path => [key => value, ...], ...] */
    private array $metadata = [];

    /**
     * Sets a flag indicates whether the primary data is a collection.
     */
    public function setCollection(bool $isCollection): void
    {
        $this->path = null;
        $this->rootIndex = $isCollection ? -1 : null;
    }

    /**
     * Sets metadata that are linked to data.
     *
     * @param array $metadata [data item path => [key => value, ...], ...]
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * Gets metadata that are linked to data.
     *
     * @return array $metadata [data item path => [key => value, ...], ...]
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Adds the given entity data to the end of the data stack.
     */
    public function addEntity(array $data = []): void
    {
        if (-1 !== $this->lastIndex && !$this->isLastElementAssociation()) {
            $this->lastIndex++;
            $this->stack[$this->lastIndex] = null;
        }
        $this->lastIndex++;
        $this->stack[$this->lastIndex] = $data;
        if (null !== $this->rootIndex && 0 === $this->lastIndex) {
            $this->rootIndex++;
        }
        $this->path = null;
    }

    /**
     * Sets the given entity data as the last element of the data stack.
     * If the last element contains data for another entity,
     * it will be overridden by the given data.
     */
    public function setEntity(array $data): void
    {
        if (-1 === $this->lastIndex) {
            $this->lastIndex++;
            $this->path = null;
        } elseif ($this->isLastElementAssociation()) {
            unset($this->stack[$this->lastIndex]);
            $this->lastIndex--;
            $this->path = null;
        }
        $this->stack[$this->lastIndex] = $data;
    }

    /**
     * Sets the given association data as the last element of the data stack.
     * If the last element contains data for another association,
     * it will be overridden by the given data.
     */
    public function setAssociation(string $name, ?array $data, int $index = null): void
    {
        if (-1 === $this->lastIndex) {
            throw new \LogicException('Either addEntity() or setEntity() method should be called before.');
        }

        if ($this->isLastElementAssociation()) {
            if (isset($this->stack[$this->lastIndex]) && $this->stack[$this->lastIndex][2] === $name) {
                $this->stack[$this->lastIndex] = [$this->stack[$this->lastIndex][0], $data, $name, $index];
            } else {
                $this->stack[$this->lastIndex] = [$data, null, $name, $index];
            }
        } else {
            $this->lastIndex++;
            $this->stack[$this->lastIndex] = [$data, null, $name, $index];
        }
        $this->path = null;
    }

    /**
     * Sets the item index for a current association.
     */
    public function setAssociationIndex(int $index = null): void
    {
        if (-1 === $this->lastIndex || !$this->isLastElementAssociation()) {
            throw new \LogicException('setAssociation() method should be called before.');
        }

        $this->stack[$this->lastIndex][3] = $index;
        $this->path = null;
    }

    /**
     * Removes the last entity data from the end of the data stack.
     */
    public function removeLastEntity(): void
    {
        if (-1 === $this->lastIndex) {
            throw new \LogicException('The data stack is empty.');
        }

        if ($this->isLastElementAssociation()) {
            unset($this->stack[$this->lastIndex]);
            $this->lastIndex--;
        }
        unset($this->stack[$this->lastIndex]);
        $this->lastIndex--;
        $this->path = null;
    }

    /**
     * Removes all elements from the data stack.
     */
    public function clear(): void
    {
        $this->rootIndex = -1;
        $this->lastIndex = -1;
        $this->stack = [];
        $this->path = null;
    }

    /**
     * {@inheritdoc}
     */
    public function tryGetValue(string $propertyPath, mixed &$value): bool
    {
        if (self::PATH === $propertyPath) {
            $value = $this->getPath();

            return true;
        }

        return $this->doTryGetValue(explode('.', $propertyPath), $value);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function doTryGetValue(array $path, mixed &$value): bool
    {
        $value = null;

        $isAssociationData = $this->isLastElementAssociation();
        $data = $this->stack[$this->lastIndex];
        $firstIndex = 0;
        if ('_' === $path[0]) {
            if (!$isAssociationData) {
                // the "_" is allowed only for associations
                return false;
            }
            $isAssociationData = false;
            $data = $this->stack[$this->lastIndex - 1];
            $firstIndex++;
        }

        $commonData = null;
        if ($isAssociationData) {
            [$commonData, $data] = $data;
        }

        $hasValue = true;
        $lastIndex = \count($path) - 1;
        for ($i = $firstIndex; $i <= $lastIndex; $i++) {
            $key = $path[$i];
            if (!\is_array($data) || !\array_key_exists($key, $data)) {
                if ($i === $firstIndex && \is_array($commonData) && \array_key_exists($key, $commonData)) {
                    $data = $commonData[$key];
                } elseif (0 === $i && 0 === $lastIndex) {
                    $metadataValue = $this->getMetadataValue($key);
                    if (null !== $metadataValue) {
                        $data = $metadataValue;
                    } else {
                        $hasValue = false;
                    }
                } else {
                    $hasValue = false;
                }
                break;
            }
            $data = $data[$key];
        }
        if ($hasValue) {
            $value = $data;
        }

        return $hasValue;
    }

    private function getMetadataValue(string $propertyPath): mixed
    {
        return $this->metadata[$this->getPath()][$propertyPath] ?? null;
    }

    private function getPath(): string
    {
        if (null === $this->path) {
            $keys = [];
            if (null !== $this->rootIndex && -1 !== $this->rootIndex) {
                $keys[] = (string)$this->rootIndex;
            }
            foreach ($this->stack as $index => $data) {
                if (0 !== $index % 2) {
                    $keys[] = $data[2];
                    if (null !== $data[3]) {
                        $keys[] = (string)$data[3];
                    }
                }
            }
            $this->path = implode('.', $keys);
        }

        return $this->path;
    }

    private function isLastElementAssociation(): bool
    {
        return 0 !== $this->lastIndex % 2;
    }
}
