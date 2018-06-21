<?php

/**
 * This file is a copy of {@see Zend\Mail\Header\HeaderLoader}
 *
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 */

namespace Oro\Bundle\ImapBundle\Mail\Header;

use \Zend\Mail\Header\HeaderLoader as BaseHeaderLoader;

/**
 * The Zend Framework zend-mail package provides more strictly rules for email headers.
 * To simplify checks they need to be overridden as the zend-mail is used only for import emails, and it is assumed
 * that if email exists on the mail server it has passed all checks and can be safety imported.
 */
class HeaderLoader extends BaseHeaderLoader
{
    /**
     * {@inheritdoc}
     */
    protected $plugins = [
        'bcc'                       => 'Oro\Bundle\ImapBundle\Mail\Header\Bcc',
        'cc'                        => 'Oro\Bundle\ImapBundle\Mail\Header\Cc',
        'contenttype'               => 'Oro\Bundle\ImapBundle\Mail\Header\ContentType',
        'content_type'              => 'Oro\Bundle\ImapBundle\Mail\Header\ContentType',
        'content-type'              => 'Oro\Bundle\ImapBundle\Mail\Header\ContentType',
        'contenttransferencoding'   => 'Zend\Mail\Header\ContentTransferEncoding',
        'content_transfer_encoding' => 'Zend\Mail\Header\ContentTransferEncoding',
        'content-transfer-encoding' => 'Zend\Mail\Header\ContentTransferEncoding',
        'date'                      => 'Zend\Mail\Header\Date',
        'from'                      => 'Oro\Bundle\ImapBundle\Mail\Header\From',
        'message-id'                => 'Zend\Mail\Header\MessageId',
        'mimeversion'               => 'Zend\Mail\Header\MimeVersion',
        'mime_version'              => 'Zend\Mail\Header\MimeVersion',
        'mime-version'              => 'Zend\Mail\Header\MimeVersion',
        'received'                  => 'Zend\Mail\Header\Received',
        'replyto'                   => 'Zend\Mail\Header\ReplyTo',
        'reply_to'                  => 'Zend\Mail\Header\ReplyTo',
        'reply-to'                  => 'Zend\Mail\Header\ReplyTo',
        'sender'                    => 'Oro\Bundle\ImapBundle\Mail\Header\Sender',
        'subject'                   => 'Oro\Bundle\ImapBundle\Mail\Header\Subject',
        'to'                        => 'Zend\Mail\Header\To',
    ];
}
