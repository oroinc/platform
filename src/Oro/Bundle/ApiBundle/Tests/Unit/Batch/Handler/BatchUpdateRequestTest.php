<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Handler;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateRequest;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\GaufretteBundle\FileManager;

class BatchUpdateRequestTest extends \PHPUnit\Framework\TestCase
{
    public function testRequest()
    {
        $version = '1.2';
        $requestType = new RequestType([RequestType::REST]);
        $operationId = 123;
        $supportedEntityClasses = ['Test\Entity'];
        $file = new ChunkFile('api_1_chunk', 0, 0);
        $fileManager = $this->createMock(FileManager::class);

        $request = new BatchUpdateRequest(
            $version,
            $requestType,
            $operationId,
            $supportedEntityClasses,
            $file,
            $fileManager
        );

        self::assertSame($version, $request->getVersion());
        self::assertSame($requestType, $request->getRequestType());
        self::assertSame($operationId, $request->getOperationId());
        self::assertSame($supportedEntityClasses, $request->getSupportedEntityClasses());
        self::assertSame($file, $request->getFile());
        self::assertSame($fileManager, $request->getFileManager());
    }
}
