<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Model;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Model\FileContentProvider;

class FileContentProviderTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_FILE_NAME = 'some_file.txt';

    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var FileContentProvider */
    private $fileContentProvider;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);

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
