<?php

namespace Oro\Bundle\LayoutBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Traits\DoctrineTrait;

/**
 * Implementation of {@see \Symfony\Component\Cache\Adapter\DoctrineAdapter} that hash the id keys to be sure
 * that the final id can be used as filename of cache file.
 */
class ExpressionLanguageDoctrineAdapter extends AbstractAdapter
{
    use DoctrineTrait {
        doFetch as traitDoFetch;
        doHave as traitDoHave;
        doDelete as traitDoDelete;
        doSave as traitDoSave;
    }

    public function __construct(CacheProvider $provider, string $namespace = '', int $defaultLifetime = 0)
    {
        parent::__construct('', $defaultLifetime);
        $this->provider = $provider;
        $provider->setNamespace($namespace);
    }

    /**
     * {@inheritDoc}
     */
    protected function doFetch(array $ids)
    {
        $updatedIds = [];
        foreach ($ids as $id) {
            $updatedIds[] = $this->hashId($id);
        }

        return $this->traitDoFetch($updatedIds);
    }

    /**
     * {@inheritDoc}
     */
    protected function doHave($id)
    {
        return $this->traitDoHave($this->hashId($id));
    }

    /**
     * {@inheritDoc}
     */
    protected function doDelete(array $ids)
    {
        $updatedIds = [];
        foreach ($ids as $id) {
            $updatedIds[] = $this->hashId($id);
        }

        return $this->traitDoDelete($updatedIds);
    }

    /**
     * {@inheritDoc}
     */
    protected function doSave(array $values, int $lifetime)
    {
        $updatedValues = [];
        foreach ($values as $key => $value) {
            $updatedValues[$this->hashId($key)] = $value;
        }

        return $this->traitDoSave($updatedValues, $lifetime);
    }

    private function hashId(string $id): string
    {
        return base_convert(md5($id), 16, 36) . base_convert(sha1($id), 16, 36);
    }
}
