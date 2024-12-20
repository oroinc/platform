<?php

namespace Oro\Bundle\ImapBundle\Mail\Storage;

/**
 * Gmail IMAP protocol implementation.
 */
class GmailImap extends Imap
{
    const DEFAULT_GMAIL_HOST = 'imap.gmail.com';
    const DEFAULT_GMAIL_PORT = '993';
    const DEFAULT_GMAIL_SSL = 'ssl';

    const DEFAULT_GMAIL_SMTP_HOST = 'smtp.gmail.com';
    const DEFAULT_GMAIL_SMTP_PORT = '465';
    const DEFAULT_GMAIL_SMTP_SSL = 'ssl';

    const X_GM_MSGID = 'X-GM-MSGID';
    const X_GM_THRID = 'X-GM-THRID';
    const X_GM_LABELS = 'X-GM-LABELS';

    public function __construct($params)
    {
        parent::__construct($params);
        array_push($this->getMessageItems, self::X_GM_MSGID, self::X_GM_THRID, self::X_GM_LABELS);
    }

    #[\Override]
    public function search(array $criteria)
    {
        if (!empty($criteria)) {
            $lastItem = end($criteria);
            if (str_starts_with($lastItem, '"') && str_ends_with($lastItem, '"')) {
                array_unshift($criteria, 'X-GM-RAW');
            }
        }

        return parent::search($criteria);
    }

    #[\Override]
    protected function getCapability()
    {
        $capability   = parent::getCapability();
        $capability[] = self::CAPABILITY_MSG_MULTI_FOLDERS;

        return $capability;
    }

    #[\Override]
    protected function setExtHeaders(&$headers, array $data)
    {
        parent::setExtHeaders($headers, $data);

        $headers->addHeaderLine(self::X_GM_MSGID, $data[self::X_GM_MSGID]);
        $headers->addHeaderLine(self::X_GM_THRID, $data[self::X_GM_THRID]);
        $headers->addHeaderLine(
            self::X_GM_LABELS,
            isset($data[self::X_GM_LABELS]) ? $data[self::X_GM_LABELS] : array()
        );
    }
}
