<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Model;

use Oro\Bundle\AttachmentBundle\Model\FileContentProvider;

class FileContentProviderTest extends \PHPUnit\Framework\TestCase
{
    const TEST_FILE_NAME = 'some_file.txt';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $fileManager;

    /** @var FileContentProvider */
    protected $fileContentProvider;

    public function setUp()
    {
        $this->fileManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\FileManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileContentProvider = new FileContentProvider(
            self::TEST_FILE_NAME,
            $this->fileManager
        );
    }

    public function testGetData()
    {
        $data = 'some data';

        $this->fileManager->expects($this->once())
            ->method('getContent')
            ->with(self::TEST_FILE_NAME)
            ->willReturn($data);

        $this->assertSame($data, $this->fileContentProvider->getData());
    }
}
