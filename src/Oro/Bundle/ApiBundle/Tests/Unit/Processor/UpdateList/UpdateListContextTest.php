<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Processor\UpdateList\UpdateListContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

class UpdateListContextTest extends \PHPUnit\Framework\TestCase
{
    private UpdateListContext $context;

    protected function setUp(): void
    {
        $this->context = new UpdateListContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
    }

    public function testRequestData()
    {
        self::assertNull($this->context->getRequestData());

        $resource = fopen('php://memory', 'rb+');
        try {
            $this->context->setRequestData($resource);
            self::assertSame($resource, $this->context->getRequestData());
        } finally {
            fclose($resource);
        }

        $this->context->setRequestData(null);
        self::assertNull($this->context->getRequestData());
    }

    public function testTargetFileName()
    {
        self::assertNull($this->context->getTargetFileName());

        $fileName = 'test';
        $this->context->setTargetFileName($fileName);
        self::assertSame($fileName, $this->context->getTargetFileName());

        $this->context->setTargetFileName(null);
        self::assertNull($this->context->getTargetFileName());
    }

    public function testOperationId()
    {
        self::assertNull($this->context->getOperationId());

        $operationId = 123;
        $this->context->setOperationId($operationId);
        self::assertSame($operationId, $this->context->getOperationId());

        $this->context->setOperationId(null);
        self::assertNull($this->context->getOperationId());
    }
}
