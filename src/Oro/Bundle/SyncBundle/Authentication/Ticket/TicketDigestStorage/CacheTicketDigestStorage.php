<?php

namespace Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestStorage;

use Doctrine\Common\Cache\CacheProvider;

/**
 * Sync authentication tickets digests storage that use cache as the storage.
 */
class CacheTicketDigestStorage implements TicketDigestStorageInterface
{
    /** @var CacheProvider */
    private $cache;

    /**
     * @param CacheProvider $cache
     */
    public function __construct(CacheProvider $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function saveTicketDigest($digest)
    {
        $id = uniqid('', true);
        $this->cache->save($id, $digest);

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getTicketDigest($digestId)
    {
        $digest = $this->cache->fetch($digestId);
        if (false === $digest) {
            $digest = '';
        } else {
            $this->cache->delete($digestId);
        }

        return $digest;
    }
}
