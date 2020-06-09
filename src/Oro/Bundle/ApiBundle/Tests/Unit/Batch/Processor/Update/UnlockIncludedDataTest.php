<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Model\IncludedData;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\UnlockIncludedData;

class UnlockIncludedDataTest extends BatchUpdateProcessorTestCase
{
    /** @var UnlockIncludedData */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new UnlockIncludedData();
    }

    public function testProcessWithoutIncludedData()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWithIncludedData()
    {
        $includedData = $this->createMock(IncludedData::class);

        $includedData->expects(self::once())
            ->method('unlock');

        $this->context->setIncludedData($includedData);
        $this->processor->process($this->context);
    }
}
