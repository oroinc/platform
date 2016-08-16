<?php

/**
 * This file is a copy of {@see Zend\Mail\Header\HeaderLoader}
 *
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 */
namespace Oro\Bundle\EmailBundle\Mail\Header;

use \Zend\Mail\Header\HeaderLoader as BaseHeaderLoader;

class HeaderLoader extends BaseHeaderLoader
{
    /**
     * @var array Pre-aliased Header plugins
     */
    protected $plugins = array(
        'bcc'          => 'Zend\Mail\Header\Bcc',
        'cc'           => 'Zend\Mail\Header\Cc',
        'contenttype'  => 'Oro\Bundle\EmailBundle\Mail\Header\ContentType',
        'content_type' => 'Oro\Bundle\EmailBundle\Mail\Header\ContentType',
        'content-type' => 'Oro\Bundle\EmailBundle\Mail\Header\ContentType',
        'date'         => 'Zend\Mail\Header\Date',
        'from'         => 'Zend\Mail\Header\From',
        'message-id'   => 'Zend\Mail\Header\MessageId',
        'mimeversion'  => 'Zend\Mail\Header\MimeVersion',
        'mime_version' => 'Zend\Mail\Header\MimeVersion',
        'mime-version' => 'Zend\Mail\Header\MimeVersion',
        'received'     => 'Zend\Mail\Header\Received',
        'replyto'      => 'Zend\Mail\Header\ReplyTo',
        'reply_to'     => 'Zend\Mail\Header\ReplyTo',
        'reply-to'     => 'Zend\Mail\Header\ReplyTo',
        'sender'       => 'Zend\Mail\Header\Sender',
        'subject'      => 'Zend\Mail\Header\Subject',
        'to'           => 'Zend\Mail\Header\To',
    );
}
