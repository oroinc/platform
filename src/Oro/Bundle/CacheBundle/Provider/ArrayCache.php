<?php

namespace Oro\Bundle\CacheBundle\Provider;

use Doctrine\Common\Cache\ArrayCache as BaseArrayCache;

/**
 * This class handles a case when object is persisted in cache and then modified (which results in that cache data is
 * modified too).
 */
class ArrayCache extends BaseArrayCache
{
    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        if (is_object($data)) {
            $data = unserialize(serialize($data));
        }

        return parent::doSave($id, $data, $lifeTime);
    }
}
