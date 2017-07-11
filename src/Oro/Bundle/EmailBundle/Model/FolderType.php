<?php

namespace Oro\Bundle\EmailBundle\Model;

class FolderType
{
    const INBOX  = 'inbox';
    const SENT   = 'sent';
    const TRASH  = 'trash';
    const DRAFTS = 'drafts';
    const SPAM   = 'spam';
    const OTHER  = 'other';

    /**
     * @deprecated since 2.3. Use outgoingTypes() instead
     */
    public static function outcomingTypes()
    {
        return self::outgoingTypes();
    }

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
