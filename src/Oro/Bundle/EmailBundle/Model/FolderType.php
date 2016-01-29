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

    public static function outcomingTypes()
    {
        return [
            static::SENT,
            static::DRAFTS,
        ];
    }

    public static function incomingTypes()
    {
        return [
            static::INBOX,
            static::SPAM,
        ];
    }
}
