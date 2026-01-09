<?php

namespace Oro\Bundle\ImapBundle\Manager\DTO;

/**
 * Data transfer object representing a unique IMAP message identifier.
 *
 * This DTO encapsulates the IMAP UID (unique identifier) and UID validity values that together
 * uniquely identify a message within an IMAP mailbox. These values are essential for tracking
 * messages across synchronization operations and ensuring message consistency when the mailbox
 * state changes.
 */
class ItemId
{
    /**
     * @var int
     */
    private $uid;

    /**
     * @var int
     */
    private $uidValidity;

    /**
     * Constructor
     *
     * @param int $uid
     * @param int $uidValidity
     */
    public function __construct($uid, $uidValidity)
    {
        $this->uid = $uid;
        $this->uidValidity = $uidValidity;
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param int $uid
     * @return $this
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * @return int
     */
    public function getUidValidity()
    {
        return $this->uidValidity;
    }

    /**
     * @param int $uidValidity
     * @return $this
     */
    public function setUidValidity($uidValidity)
    {
        $this->uidValidity = $uidValidity;

        return $this;
    }
}
