<?php

namespace Oro\Bundle\EmailBundle\Sync;

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
