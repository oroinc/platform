<?php

namespace Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestStorage;

/**
 * Represents a storage for authentication tickets digests.
 */
interface TicketDigestStorageInterface
{
    /**
     * Saves the authentication ticket digest and returns its identifier.
     *
     * @param string $digest
     *
     * @return string The digest identifier
     */
    public function saveTicketDigest($digest);

    /**
     * Gets the authentication ticket digest by its identifier and remove it from the storage.
     *
     * @param string $digestId
     *
     * @return string The digest or empty string if it was not found
     */
    public function getTicketDigest($digestId);
}
