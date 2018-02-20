<?php

namespace Oro\Bundle\ImapBundle\Mail\Storage;

use Oro\Bundle\EmailBundle\Model\FolderType;
use Zend\Mail\Storage\Folder as BaseFolder;

class Folder extends BaseFolder
{
    const FLAG_SENT   = 'Sent';
    const FLAG_SPAM   = 'Spam';
    const FLAG_TRASH  = 'Trash';
    const FLAG_DRAFTS = 'Drafts';
    const FLAG_INBOX  = 'Inbox';
    const FLAG_ALL    = 'All';

    /** @var array */
    protected $flagTypeMap = [
        self::FLAG_INBOX  => FolderType::INBOX,
        self::FLAG_SENT   => FolderType::SENT,
        self::FLAG_DRAFTS => FolderType::DRAFTS,
        self::FLAG_TRASH  => FolderType::TRASH,
        self::FLAG_SPAM   => FolderType::SPAM,
    ];

    /** @var array */
    protected $knownFolderNameMap = [
        'Inbox'            => FolderType::INBOX,
        'INBOX'            => FolderType::INBOX,

        'Drafts'           => FolderType::DRAFTS,
        'INBOX.Drafts'     => FolderType::DRAFTS,

        'Spam'             => FolderType::SPAM,
        'Junk'             => FolderType::SPAM,
        'Junk E-mail'      => FolderType::SPAM,
        'INBOX.Junk'       => FolderType::SPAM,

        'Sent'             => FolderType::SENT,
        'SentBox'          => FolderType::SENT,
        'Sent Items'       => FolderType::SENT,
        'Sent Messages'    => FolderType::SENT,
        'INBOX.Sent'       => FolderType::SENT,

        'Trash'            => FolderType::TRASH,
        'Deleted'          => FolderType::TRASH,
        'Deleted Items'    => FolderType::TRASH,
        'Deleted Messages' => FolderType::TRASH,
        'INBOX.Trash'      => FolderType::TRASH
    ];

    /** @var string[] */
    public $flags = null;

    /** @var string folder type (sent, inbox, etc) */
    public $type = null;

    /**
     * Determines whether this folder is marked by the given flag
     *
     * @param string|array $flags one flag or an array with multiple flags
     *
     * @return bool
     */
    public function hasFlag($flags)
    {
        if (empty($this->flags)) {
            return false;
        }

        if (false == is_array($flags)) {
            $flags = [$flags];
        }

        $flags = array_map(
            function ($item) {
                if (false === strpos($item, '\\')) {
                    $item = '\\' . $item;
                }
                return $item;
            },
            $flags
        );

        if (count($flags) > 1) {
            return count(array_intersect($this->flags, $flags)) > 0;
        } else {
            return in_array($flags[0], $this->flags);
        }
    }

    /**
     * Sets flags
     *
     * @param string[] $flags
     */
    public function setFlags(array $flags)
    {
        if ($this->flags === null) {
            $this->flags = $flags;
        } else {
            foreach ($flags as $flag) {
                if (!in_array($flag, $this->flags)) {
                    $this->flags[] = $flag;
                }
            }
        }
    }

    /**
     * Adds a flag
     *
     * @param string $flag
     */
    public function addFlag($flag)
    {
        if ($this->flags === null) {
            $this->flags = array();
        }
        if (!(strpos($flag, '\\') === 0)) {
            $flag = '\\' . $flag;
        }
        if (!in_array($flag, $this->flags)) {
            $this->flags[] = $flag;
        }
    }

    /**
     * Deletes a flag
     *
     * @param string $flag
     */
    public function deleteFlag($flag)
    {
        if ($this->flags !== null) {
            if (!(strpos($flag, '\\') === 0)) {
                $flag = '\\' . $flag;
            }
            unset($this->flags[$flag]);
        }
    }

    /**
     * Guess folder type based on it's flags
     *
     * @return string
     */
    public function guessFolderType()
    {
        $this->type = FolderType::OTHER;
        foreach ($this->flagTypeMap as $flag => $type) {
            if ($this->hasFlag($flag)) {
                $this->type = $type;
                break;
            }
        }
        // In case the IMAP server doesn't support the special-use mailboxes extension,
        // try to guess the type using hard-coded folder names.
        if ($this->type === FolderType::OTHER) {
            $guessedType = $this->guessFolderTypeByName();

            if ($guessedType) {
                $this->type = $guessedType;
            }
        }

        return $this->type;
    }

    /**
     * Try to guess folder type using known special folder names.
     *
     * @return string|bool
     */
    public function guessFolderTypeByName()
    {
        if (array_key_exists($this->getGlobalName(), $this->knownFolderNameMap)) {
            return $this->knownFolderNameMap[$this->getGlobalName()];
        }

        return false;
    }
}
