<?php

namespace Oro\Bundle\ImapBundle\OriginSyncCredentials;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

/**
 * Storage of wrong credential sync origins.
 */
interface WrongCredentialsOriginsDriverInterface
{
    /**
     * Stores information about wrong credential sync origins
     *
     * @param integer $emailOriginId
     * @param integer $ownerId
     */
    public function addOrigin($emailOriginId, $ownerId = null);

    /**
     * Returns array with wrong credential sync origins.
     *
     * @return UserEmailOrigin[]
     */
    public function getAllOrigins();

    /**
     * Returns array with wrong credential sync origins for given user  owner id.
     *
     * @return UserEmailOrigin[]
     */
    public function getAllOriginsByOwnerId($ownerId = null);

    /**
     * Removes the origin information.
     *
     * @param integer $emailOriginId
     */
    public function deleteOrigin($emailOriginId);

    /**
     * Clears the storage.
     */
    public function deleteAllOrigins();
}
