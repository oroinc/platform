<?php

namespace Oro\Bundle\ImapBundle\Mail\Storage;

/**
 * Microsoft Office 365 IMAP
 */
class Office365Imap extends Imap
{
    public const DEFAULT_IMAP_HOST = 'outlook.office365.com';
    public const DEFAULT_IMAP_PORT = '993';
    public const DEFAULT_IMAP_ENCRYPTION = 'ssl';

    public const DEFAULT_SMTP_HOST = 'smtp.office365.com';
    public const DEFAULT_SMTP_PORT = '587';
    public const DEFAULT_SMTP_ENCRYPTION = 'tls';
}
