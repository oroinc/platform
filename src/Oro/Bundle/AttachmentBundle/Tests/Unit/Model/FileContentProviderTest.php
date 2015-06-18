<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Model;

use Oro\Bundle\AttachmentBundle\Model\FileContentProvider;

class FileContentProviderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_FILE_NAME = 'some_file.txt';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attachmentManager;

    /** @var FileContentProvider */
    protected $fileContentProvider;

    public function setUp()
    {
        $this->attachmentManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileContentProvider = new FileContentProvider(
            self::TEST_FILE_NAME,
            $this->attachmentManager
        );
    }

    public function testGetData()
    {
        $data = 'some data';

        $this->attachmentManager->expects($this->once())
            ->method('getContent')
            ->with(self::TEST_FILE_NAME)
            ->willReturn($data);

        $this->assertSame($data, $this->fileContentProvider->getData());
    }
}
