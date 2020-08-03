<?php

/**
 * Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 * - Redistributions of source code must retain the above copyright notice, this list of conditions and the following
 * disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the
 * following disclaimer in the documentation and/or other materials provided with the distribution.
 * - Neither the name of Laminas Foundation nor the names of its contributors may be used to endorse or promote
 * products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This file is a copy of {@see Laminas\Mail\Header\HeaderLoader}
 */

namespace Oro\Bundle\ImapBundle\Mail\Header;

use Laminas\Mail\Header\HeaderLoader as BaseHeaderLoader;

/**
 * Header loader that changes the headers classes to overridden ORO classes.
 */
class HeaderLoader extends BaseHeaderLoader
{
    /**
     * {@inheritdoc}
     */
    protected $plugins = [
        'bcc' => 'Oro\Bundle\ImapBundle\Mail\Header\Bcc',
        'cc' => 'Oro\Bundle\ImapBundle\Mail\Header\Cc',
        'contenttype' => 'Oro\Bundle\ImapBundle\Mail\Header\ContentType',
        'content_type' => 'Oro\Bundle\ImapBundle\Mail\Header\ContentType',
        'content-type' => 'Oro\Bundle\ImapBundle\Mail\Header\ContentType',
        'contenttransferencoding' => 'Oro\Bundle\ImapBundle\Mail\Header\ContentTransferEncoding',
        'content_transfer_encoding' => 'Oro\Bundle\ImapBundle\Mail\Header\ContentTransferEncoding',
        'content-transfer-encoding' => 'Oro\Bundle\ImapBundle\Mail\Header\ContentTransferEncoding',
        'date' => 'Laminas\Mail\Header\Date',
        'from' => 'Oro\Bundle\ImapBundle\Mail\Header\From',
        'message-id' => 'Laminas\Mail\Header\MessageId',
        'mimeversion' => 'Laminas\Mail\Header\MimeVersion',
        'mime_version' => 'Laminas\Mail\Header\MimeVersion',
        'mime-version' => 'Laminas\Mail\Header\MimeVersion',
        'received' => 'Laminas\Mail\Header\Received',
        'replyto' => 'Laminas\Mail\Header\ReplyTo',
        'reply_to' => 'Laminas\Mail\Header\ReplyTo',
        'reply-to' => 'Laminas\Mail\Header\ReplyTo',
        'sender' => 'Oro\Bundle\ImapBundle\Mail\Header\Sender',
        'subject' => 'Oro\Bundle\ImapBundle\Mail\Header\Subject',
        'to' => 'Laminas\Mail\Header\To',
    ];
}
