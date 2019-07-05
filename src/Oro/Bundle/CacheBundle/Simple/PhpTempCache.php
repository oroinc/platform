<?php

namespace Oro\Bundle\CacheBundle\Simple;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

/**
 * Non persistent cache provider that working with "php://temp" as storage
 */
class PhpTempCache implements CacheInterface
{
    /** @var int 32GB */
    private const HARD_MEMORY_LIMIT = 32 * 1024;

    private const IDX_OFFSET_KEY = 0;
    private const IDX_LENGTH_KEY = 1;

    /** @var int */
    private $maxMemoryMBs;

    /** @var bool */
    private $initialized = false;

    /** @var array */
    private $index;

    /** @var array */
    private $deleted;

    /** @var resource */
    private $dataStorage;

    /**
     * @param int $maxMemoryMBs
     *      -1 - use only memory
     *       0 - default limit
     *      [1..32768] - write buffer to disc when storage size > $maxMemoryMBs
     */
    public function __construct(int $maxMemoryMBs = 0)
    {
        $this->maxMemoryMBs = $maxMemoryMBs;
    }

    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        if ($this->maxMemoryMBs < 0) {
            $dataStoragePath = 'php://memory';
        } else {
            $dataStoragePath = 'php://temp';

            if ($this->maxMemoryMBs !== 0) {
                if ($this->maxMemoryMBs > self::HARD_MEMORY_LIMIT) {
                    trigger_error(sprintf(
                        'The specified value "%s" for the memory limit exceeds the maximum allowed %s',
                        $this->maxMemoryMBs,
                        self::HARD_MEMORY_LIMIT
                    ));

                    $this->maxMemoryMBs = self::HARD_MEMORY_LIMIT;
                }

                $dataStoragePath .= '/maxmemory' . ($this->maxMemoryMBs * 1024 * 1024);
            }
        }

        $this->index = [];
        $this->deleted = [];
        $this->dataStorage = fopen($dataStoragePath, 'w+b');
        $this->initialized = true;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        $this->initialize();
        $this->assertKey($key);

        if (!$this->has($key)) {
            return $default;
        }

        if (fseek($this->dataStorage, $this->index[$key][self::IDX_OFFSET_KEY]) !== 0) {
            throw new \RuntimeException('Temp cache invalid: unable seek specified offset in storage');
        }

        $data = fread($this->dataStorage, $this->index[$key][self::IDX_LENGTH_KEY]);

        if ($data === false) {
            throw new \RuntimeException('Temp cache invalid: unable reed data');
        }

        return \unserialize($data);
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null): bool
    {
        $this->initialize();
        $this->assertKey($key);

        $this->delete($key);

        $data = \serialize($value);
        $dataSize = strlen($data);
        if ($dataSize === 0) {
            throw new \InvalidArgumentException('Try allocate position for empty data');
        }

        $this->index[$key] = [
            self::IDX_OFFSET_KEY => null,
            self::IDX_LENGTH_KEY => $dataSize,
        ];

        // Attempt to find a deleted fragment with a suitable size
        if ($this->deleted) {
            foreach ($this->deleted as $i => &$index) {
                if ($index[self::IDX_LENGTH_KEY] >= $dataSize) {
                    $this->index[$key][self::IDX_OFFSET_KEY] = $index[self::IDX_OFFSET_KEY];

                    if ($index[self::IDX_LENGTH_KEY] === $dataSize) {
                        unset($this->deleted[$i]);
                    } else {
                        $index[self::IDX_OFFSET_KEY] += $dataSize;
                        $index[self::IDX_LENGTH_KEY] -= $dataSize;
                    }

                    break;
                }
            }
        }

        if ($this->index[$key][self::IDX_OFFSET_KEY] === null) {
            fseek($this->dataStorage, 0, SEEK_END);
            $this->index[$key][self::IDX_OFFSET_KEY] = ftell($this->dataStorage);
        } else {
            fseek($this->dataStorage, $this->index[$key][self::IDX_OFFSET_KEY]);
        }

        $length = fwrite($this->dataStorage, $data);
        if ($length !== $dataSize) {
            throw new \RuntimeException('Temp cache invalid: wrong number of bytes written');
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key): bool
    {
        $this->assertKey($key);
        if ($this->has($key)) {
            $this->deleted[] = $this->index[$key];
            unset($this->index[$key]);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        if ($this->initialized) {
            $this->index = [];
            $this->deleted = [];
            fclose($this->dataStorage);
            $this->initialized = false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        $this->assertIterable($keys);

        $response = [];
        foreach ($keys as $key) {
            $this->assertKey($key);
            $response[$key] = $this->get($key, $default);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null): bool
    {
        $this->assertIterable($values);

        $success = true;
        foreach ($values as $key => $value) {
            $this->assertKey($key);
            $success = $success && $this->set($key, $value, $ttl);
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys): bool
    {
        $this->assertIterable($keys);

        $success = true;
        foreach ($keys as $key) {
            $this->assertKey($key);
            $success = $success && $this->delete($key);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key): bool
    {
        $this->assertKey($key);
        return isset($this->index[$key]);
    }

    /**
     * @param mixed $key
     * @throws InvalidArgumentException
     */
    private function assertKey($key): void
    {
        if (!\is_string($key) || \is_numeric($key)) {
            throw new InvalidArgumentException(
                sprintf('Invalid key "%s", not numeric string required', \is_scalar($key) ? $key : \gettype($key))
            );
        }
    }

    private function assertIterable($iterable)
    {
        if (!\is_iterable($iterable)) {
            throw new \InvalidArgumentException(sprintf(
                'Argument must be an array or a Traversable, got "%s".',
                \is_object($iterable) ? \get_class($iterable) : \gettype($iterable)
            ));
        }
    }
}
