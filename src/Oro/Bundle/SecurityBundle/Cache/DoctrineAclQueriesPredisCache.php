<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Oro\Bundle\RedisConfigBundle\Doctrine\Common\Cache\PredisCache;
use Predis\ClientInterface;

/**
 * The Redis cache for Doctrine ACL queries that physically removes old data instead of increasing cache id.
 */
class DoctrineAclQueriesPredisCache extends PredisCache
{
    private ClientInterface $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
        parent::__construct($client);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAll()
    {
        $pattern = $this->getNamespace() . '\[*';
        $cursor = null;
        do {
            $keys = $this->client->scan($cursor, 'MATCH', $pattern, 'COUNT', 1000);
            if (isset($keys[1]) && \is_array($keys[1])) {
                $cursor = $keys[0];
                $keys = $keys[1];
            }
            if ($keys) {
                $this->doDeleteMultiple($keys);
            }
        } while ($cursor = (int) $cursor);

        // remove namespace cache key
        $this->doDelete(sprintf(self::DOCTRINE_NAMESPACE_CACHEKEY, $this->getNamespace()));

        return true;
    }
}
