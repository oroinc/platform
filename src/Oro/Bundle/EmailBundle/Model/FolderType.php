<?php

namespace Oro\Bundle\EmailBundle\Model;

/**
 * Provides possible types of folders.
 */
class FolderType
{
    public const INBOX = 'inbox';
    public const SENT = 'sent';
    public const TRASH = 'trash';
    public const DRAFTS = 'drafts';
    public const SPAM = 'spam';
    public const OTHER = 'other';

    /**
     * @return string[]
     */
    public static function outgoingTypes(): array
    {
        return [
            static::SENT,
            static::DRAFTS,
        ];
    }

    /**
     * @return string[]
     */
    public static function incomingTypes(): array
    {
        return [
            static::INBOX,
            static::SPAM,
        ];
    }
}
