<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Behat\Isolation;

use Predis\Client;
use Symfony\Component\Filesystem\Filesystem;

class RedisCacheManipulator
{
    /** @var Client */
    private $redisClient;

    /** @var string */
    private $name;

    /** @var Filesystem */
    private $filesystem;

    /** @var string */
    private $fileName;

    /** @var array */
    private $data;

    public function __construct(Client $redisClient, string $name)
    {
        $this->redisClient = $redisClient;
        $this->name = $name;
        $this->filesystem = new Filesystem();
        $this->fileName = \sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'behat_redis_' . \strtolower($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Save state of cache and return values count.
     */
    public function saveRedisState(): int
    {
        $keys = $this->redisClient->keys('*');
        if (empty($keys)) {
            return 0;
        }

        $values = $this->redisClient->mget($keys);

        $this->data = \array_combine($keys, $values);

        $this->filesystem->dumpFile($this->fileName, \json_encode($this->data));

        return count($this->data);
    }

    /**
     * Restore state of cache and return values count.
     */
    public function restoreRedisState(): int
    {
        if (!is_array($this->data)) {
            $this->restoreData();
        }

        if (!$this->data) {
            return 0;
        }

        $this->redisClient->flushdb();
        $this->redisClient->mset($this->data);
        $this->filesystem->remove($this->fileName);

        return count($this->data);
    }

    public function restoreData(): array
    {
        $this->data = [];

        if ($this->filesystem->exists($this->fileName)) {
            $this->data = \json_decode(file_get_contents($this->fileName), true);

            if (!$this->data) {
                $this->data = [];
            }
        }

        return $this->data;
    }
}
