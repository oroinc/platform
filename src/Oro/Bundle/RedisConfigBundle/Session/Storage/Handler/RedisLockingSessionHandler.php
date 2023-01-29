<?php

namespace Oro\Bundle\RedisConfigBundle\Session\Storage\Handler;

/**
 * Copyright (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * This file is a copy of {@see \Snc\RedisBundle\Session\Storage\Handler\RedisSessionHandler}
 */
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\AbstractSessionHandler;

/**
 * Redis based session storage with session locking support.
 */
class RedisLockingSessionHandler extends AbstractSessionHandler
{
    use LoggerAwareTrait;

    /**
     * @var int Default PHP max execution time in seconds
     */
    private const DEFAULT_MAX_EXECUTION_TIME = 30;

    private string $logLevel = LogLevel::INFO;

    protected \Predis\Client|\Redis $redis;

    /**
     * @var int Time to live in seconds
     */
    protected int $ttl;

    /**
     * @var string Key prefix for shared environments
     */
    protected string $prefix;

    /**
     * @var bool Indicates an sessions should be locked
     */
    protected bool $locking;

    /**
     * @var bool Indicates an active session lock
     */
    protected bool $locked;

    /**
     * @var string Session lock key
     */
    protected string $lockKey;

    /**
     * @var string|null Session lock token
     */
    protected ?string $token = null;

    /**
     * @var int Microseconds to wait between acquire lock tries
     */
    protected int $spinLockWait;

    /**
     * @var int Maximum amount of seconds to wait for the lock
     */
    protected int $lockMaxWait;

    /**
     * Redis session storage constructor.
     *
     * @param \Predis\Client|\Redis $redis Redis database connection
     * @param array $options Session options
     * @param string $prefix Prefix to use when writing session data
     * @param bool $locking Indicates an sessions should be locked
     * @param int $spinLockWait Microseconds to wait between acquire lock tries
     */
    public function __construct(
        \Predis\Client|\Redis $redis,
        array $options = [],
        string $prefix = 'session',
        bool $locking = true,
        int $spinLockWait = 150000
    ) {
        $this->redis = $redis;
        $this->ttl = isset($options['gc_maxlifetime']) ? (int)$options['gc_maxlifetime'] : 0;
        if (isset($options['cookie_lifetime']) && $options['cookie_lifetime'] > $this->ttl) {
            $this->ttl = (int)$options['cookie_lifetime'];
        }
        $this->prefix = $prefix;

        $this->locking = $locking;
        $this->locked = false;
        $this->spinLockWait = $spinLockWait;
        $this->lockMaxWait = ini_get('max_execution_time');
        if (!$this->lockMaxWait) {
            $this->lockMaxWait = self::DEFAULT_MAX_EXECUTION_TIME;
        }

        if (true === $locking) {
            register_shutdown_function(array($this, 'shutdown'));
        }
    }

    public function setLogLevel(string $logLevel): void
    {
        $this->logLevel = $logLevel;
    }

    /**
     * Change the default TTL.
     */
    public function setTtl(int $ttl)
    {
        $this->ttl = $ttl;
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        if ($this->locking && $this->locked) {
            $this->unlockSession();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function updateTimestamp(string $id, string $data): bool
    {
        if (0 < $this->ttl) {
            $this->redis->expire($this->getRedisKey($id), $this->ttl);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime): int|false
    {
        // not required here because redis will auto expire the keys as long as ttl is set
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRead($sessionId): string
    {
        if ($this->locking && !$this->locked && !$this->lockSession($sessionId)) {
            return false;
        }

        return $this->redis->get($this->getRedisKey($sessionId)) ?: '';
    }

    /**
     * {@inheritdoc}
     */
    protected function doWrite($sessionId, $data): bool
    {
        if (0 < $this->ttl) {
            $this->redis->setex($this->getRedisKey($sessionId), $this->ttl, $data);
        } else {
            $this->redis->set($this->getRedisKey($sessionId), $data);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDestroy($sessionId): bool
    {
        $this->redis->del($this->getRedisKey($sessionId));
        $this->close();

        return true;
    }

    /**
     * Lock the session data.
     */
    protected function lockSession($sessionId): bool
    {
        $attempts = (1000000 / $this->spinLockWait) * $this->lockMaxWait;
        $this->token = uniqid();
        $this->lockKey = $this->getRedisKey($sessionId) . '.lock';

        $setFunction = function ($redis, $key, $token, $ttl) {
            if ($redis instanceof \Redis) {
                return $redis->set($key, $token, ['NX', 'PX' => $ttl]);
            }

            return $redis->set($key, $token, 'PX', $ttl, 'NX');
        };

        for ($i = 0; $i < $attempts; ++$i) {
            // We try to aquire the lock
            $success = $setFunction($this->redis, $this->lockKey, $this->token, $this->lockMaxWait * 1000 + 1);
            if ($success) {
                $this->locked = true;

                return true;
            }

            $this->logFailedSessionLock($sessionId);
            usleep($this->spinLockWait);
        }

        return false;
    }

    /**
     * Unlock the session data.
     */
    private function unlockSession(): void
    {
        if ($this->redis instanceof \Redis) {
            // If we have the right token, then delete the lock
            $script = <<<LUA
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
LUA;

            $token = $this->redis->_serialize($this->token);
            $this->redis->eval($script, [$this->lockKey, $token], 1);
        } else {
            $this->redis->getProfile()->defineCommand('sncFreeSessionLock', FreeLockCommand::class);
            $this->redis->sncFreeSessionLock($this->lockKey, $this->token);
        }
        $this->locked = false;
        $this->token = null;
    }

    private function logFailedSessionLock($sessionId): void
    {
        $message = sprintf(
            '[REDIS %s] Lock %s $lockMaxWait=%s $ttl=%s',
            $sessionId,
            json_encode(array_reverse($this->getStackTrace())),
            $this->lockMaxWait,
            $this->ttl
        );

        if (array_key_exists('REQUEST_URI', $_SERVER)) {
            $message = sprintf('%s $route=%s', $message, parse_url($_SERVER['REQUEST_URI'])['path']);
        }

        $this->logger->log($this->logLevel, $message);
    }

    private function getStackTrace(): array
    {
        $trace = [];
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $line) {
            if (empty($line['class'])) {
                $trace[$line['function']] = [];

                continue;
            }

            $class = explode('\\', $line['class']);
            $func = explode('\\', $line['function']);

            $trace[end($class)][] = $line['type'] . end($func);
        }
        array_pop($trace);

        return $trace;
    }
    /**
     * Prepends the given key with a user-defined prefix (if any).
     *
     * @param string $key key
     *
     * @return string prefixed key
     */
    protected function getRedisKey(string $key): string
    {
        if (empty($this->prefix)) {
            return $key;
        }

        return $this->prefix.$key;
    }

    /**
     * Shutdown handler, replacement for class destructor as it might not be called.
     */
    public function shutdown(): void
    {
        $this->close();
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->shutdown();
    }
}
