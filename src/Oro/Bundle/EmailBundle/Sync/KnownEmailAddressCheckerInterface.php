<?php

namespace Oro\Bundle\EmailBundle\Sync;

/**
 * Defines the contract for checking if an email address is known in the system.
 *
 * Implementations provide methods to verify whether an email address has been previously
 * encountered or registered in the system, supporting email synchronization optimization.
 */
interface KnownEmailAddressCheckerInterface
{
    /**
     * Check if at least one of the given email addresses is known
     *
     * @param mixed $_ Email address(es) to check
     *                 Each parameter can be a string or array of strings
     *
     * @return bool
     */
    public function isAtLeastOneKnownEmailAddress($_);

    /**
     * Check if at least one of the given email addresses belongs to the given user
     *
     * @param int   $userId The id of the user
     * @param mixed $_      Email address(es) to check
     *                      Each parameter can be a string or array of strings
     *
     * @return bool
     */
    public function isAtLeastOneUserEmailAddress($userId, $_);

    /**
     * Performs pre-loading of the given email addresses
     *
     * @param array $emails Each item can be a string or array of strings
     */
    public function preLoadEmailAddresses(array $emails);

    /**
     * Checks if at least one of given addresses belongs to a mailbox.
     *
     * @param integer $mailboxId
     * @param mixed   $_ Email address(es) to check
     *                   Each parameter can be a string or array of strings
     *
     * @return bool
     */
    public function isAtLeastOneMailboxEmailAddress($mailboxId, $_);
}
