<?php

namespace Oro\Bundle\EmailBundle\Model;

/**
 * Provides possible types of folders
 */
class FolderType
{
    const INBOX  = 'inbox';
    const SENT   = 'sent';
    const TRASH  = 'trash';
    const DRAFTS = 'drafts';
    const SPAM   = 'spam';
    const OTHER  = 'other';

    /**
     * @return string[]
     */
    public static function outgoingTypes()
    {
        return [
            static::SENT,
            static::DRAFTS,
        ];
    }

    /**
     * @return string[]
     */
    public static function incomingTypes()
    {
        return [
            static::INBOX,
            static::SPAM,
        ];
    }
}
