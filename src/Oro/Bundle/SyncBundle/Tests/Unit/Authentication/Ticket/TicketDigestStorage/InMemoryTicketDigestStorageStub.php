<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Ticket\TicketDigestStorage;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestStorage\TicketDigestStorageInterface;

class InMemoryTicketDigestStorageStub implements TicketDigestStorageInterface
{
    /** @var array [digest_id => digest, ...] */
    private $storage = [];

    /**
     * {@inheritdoc}
     */
    public function saveTicketDigest($digest)
    {
        $id = uniqid('', true);
        $this->storage[$id] = $digest;

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getTicketDigest($digestId)
    {
        $digest = '';
        if (array_key_exists($digestId, $this->storage)) {
            $digest = $this->storage[$digestId];
            unset($this->storage[$digestId]);
        }

        return $digest;
    }
}
