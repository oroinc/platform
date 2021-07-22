<?php

namespace Oro\Bundle\LayoutBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

/**
 * Implementation of {@see \Symfony\Component\Cache\Adapter\DoctrineAdapter} that hash the id keys to be sure
 * that the final id can be used as filename of cache file.
 */
class ExpressionLanguageDoctrineAdapter extends AbstractAdapter
{
    private CacheProvider $provider;

    public function __construct(CacheProvider $provider, string $namespace = '', int $defaultLifetime = 0)
    {
        parent::__construct('', $defaultLifetime);

        $provider->setNamespace($namespace);

        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch(array $ids)
    {
        $updatedIds = [];
        foreach ($ids as $id) {
            $updatedIds[] = $this->hashId($id);
        }

        $unserializeCallbackHandler = ini_set('unserialize_callback_func', parent::class.'::handleUnserializeCallback');
        try {
            return $this->provider->fetchMultiple($updatedIds);
        } catch (\Error $e) {
            $trace = $e->getTrace();

            if (isset($trace[0]['function']) && !isset($trace[0]['class'])) {
                switch ($trace[0]['function']) {
                    case 'unserialize':
                    case 'apcu_fetch':
                    case 'apc_fetch':
                        throw new \ErrorException(
                            $e->getMessage(),
                            $e->getCode(),
                            \E_ERROR,
                            $e->getFile(),
                            $e->getLine()
                        );
                }
            }

            throw $e;
        } finally {
            ini_set('unserialize_callback_func', $unserializeCallbackHandler);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doHave($id): bool
    {
        return $this->provider->contains($this->hashId($id));
    }

    /**
     * {@inheritdoc}
     */
    protected function doClear($namespace): bool
    {
        $providerNamespace = $this->provider->getNamespace();

        return isset($providerNamespace[0]) ? $this->provider->deleteAll() : $this->provider->flushAll();
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete(array $ids): bool
    {
        $result = true;
        foreach ($ids as $id) {
            $result = $this->provider->delete($this->hashId($id)) && $result;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave(array $values, int $lifetime)
    {
        $updatedValues = [];
        foreach ($values as $key => $value) {
            $updatedValues[$this->hashId($key)] = $value;
        }

        return $this->provider->saveMultiple($updatedValues, $lifetime);
    }

    private function hashId(string $id): string
    {
        return base_convert(md5($id), 16, 36) . base_convert(sha1($id), 16, 36);
    }
}
