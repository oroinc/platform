<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Mail\Storage;

use Oro\Bundle\ImapBundle\Mail\Storage\Content;
use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $content = 'testContent';
        $contentType = 'testContentType';
        $contentTransferEncoding = 'testContentTransferEncoding';
        $encoding = 'testEncoding';
        $obj = new Content($content, $contentType, $contentTransferEncoding, $encoding);

        $this->assertEquals($content, $obj->getContent());
        $this->assertEquals($contentType, $obj->getContentType());
        $this->assertEquals($contentTransferEncoding, $obj->getContentTransferEncoding());
        $this->assertEquals($encoding, $obj->getEncoding());
    }
}
