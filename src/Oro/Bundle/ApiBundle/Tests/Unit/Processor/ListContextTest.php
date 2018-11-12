<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

class ListContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataProvider */
    private $metadataProvider;

    /** @var ListContext */
    private $context;

    protected function setUp()
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->context = new ListContext($this->configProvider, $this->metadataProvider);
    }

    public function testTotalCountCallback()
    {
        self::assertNull($this->context->getTotalCountCallback());

        $totalCountCallback = [$this, 'calculateTotalCount'];

        $this->context->setTotalCountCallback($totalCountCallback);
        self::assertEquals($totalCountCallback, $this->context->getTotalCountCallback());
        self::assertEquals($totalCountCallback, $this->context->get(ListContext::TOTAL_COUNT_CALLBACK));
    }
}
