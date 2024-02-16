<?php

namespace Oro\Component\ChainProcessor;

/**
 * The base implementation of an execution context for processors.
 */
class Context extends ParameterBag implements ContextInterface
{
    /** action name */
    public const ACTION = 'action';

    private ?string $firstGroup = null;
    private ?string $lastGroup = null;
    /** @var string[] */
    private array $skippedGroups = [];
    private mixed $result = null;
    private bool $resultExists = false;
    private ?string $checksum = null;

    /**
     * {@inheritDoc}
     */
    public function getAction(): string
    {
        return $this->get(self::ACTION);
    }

    /**
     * {@inheritDoc}
     */
    public function setAction(string $action): void
    {
        $this->set(self::ACTION, $action);
    }

    /**
     * {@inheritDoc}
     */
    public function getFirstGroup(): ?string
    {
        return $this->firstGroup;
    }

    /**
     * {@inheritDoc}
     */
    public function setFirstGroup(?string $group): void
    {
        $this->firstGroup = $group;
    }

    /**
     * {@inheritDoc}
     */
    public function getLastGroup(): ?string
    {
        return $this->lastGroup;
    }

    /**
     * {@inheritDoc}
     */
    public function setLastGroup(?string $group): void
    {
        $this->lastGroup = $group;
    }

    /**
     * {@inheritDoc}
     */
    public function hasSkippedGroups(): bool
    {
        return !empty($this->skippedGroups);
    }

    /**
     * {@inheritDoc}
     */
    public function getSkippedGroups(): array
    {
        return $this->skippedGroups;
    }

    /**
     * {@inheritDoc}
     */
    public function resetSkippedGroups(): void
    {
        $this->skippedGroups = [];
    }

    /**
     * {@inheritDoc}
     */
    public function skipGroup(string $group): void
    {
        if (!\in_array($group, $this->skippedGroups, true)) {
            $this->skippedGroups[] = $group;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function undoGroupSkipping(string $group): void
    {
        $key = array_search($group, $this->skippedGroups, true);
        if (false !== $key) {
            unset($this->skippedGroups[$key]);
            $this->skippedGroups = array_values($this->skippedGroups);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hasResult(): bool
    {
        return $this->resultExists;
    }

    /**
     * {@inheritDoc}
     */
    public function getResult(): mixed
    {
        return $this->result;
    }

    /**
     * {@inheritDoc}
     */
    public function setResult(mixed $data): void
    {
        $this->result = $data;
        $this->resultExists = true;
    }

    /**
     * {@inheritDoc}
     */
    public function removeResult(): void
    {
        $this->result = null;
        $this->resultExists = false;
    }

    /**
     * {@inheritDoc}
     */
    public function getChecksum(): string
    {
        if (null === $this->checksum) {
            $this->checksum = self::buildChecksum($this->toArray());
        }

        return $this->checksum;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value): void
    {
        parent::set($key, $value);
        $this->checksum = null;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $key): void
    {
        parent::remove($key);
        $this->checksum = null;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        parent::clear();
        $this->checksum = null;
    }

    private static function buildChecksum(array $items): string
    {
        if (!$items) {
            return '';
        }

        $checksum = '';
        ksort($items, SORT_STRING);
        foreach ($items as $key => $val) {
            $val = self::prepareChecksumItem($val);
            if (null !== $val) {
                $checksum .= $key . '=' . $val . ';';
            }
        }

        return sha1($checksum);
    }

    private static function prepareChecksumItem(mixed $val): ?string
    {
        if (is_scalar($val)) {
            return $val ? 's:' . $val : null;
        }
        if (\is_array($val)) {
            $strVal = '[';
            foreach ($val as $k => $v) {
                $strVal .= $k . self::prepareChecksumItem($v);
            }

            return $strVal . ']';
        }
        if (\is_object($val) && method_exists($val, '__toString')) {
            return 'o:' . $val;
        }

        return null;
    }
}
