<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Model;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Model\FileContentProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FileContentProviderTest extends TestCase
{
    private const string TEST_FILE_NAME = 'some_file.txt';

    private FileManager&MockObject $fileManager;
    private FileContentProvider $fileContentProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);

        $this->fileContentProvider = new FileContentProvider(
            self::TEST_FILE_NAME,
            $this->fileManager
        );
    }

    public function testGetData(): void
    {
        $data = 'some data';

        $this->fileManager->expects($this->once())
            ->method('getContent')
            ->with(self::TEST_FILE_NAME)
            ->willReturn($data);

        $this->assertSame($data, $this->fileContentProvider->getData());
    }
}
