<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

class ListContextTest extends \PHPUnit\Framework\TestCase
{
    private ListContext $context;

    protected function setUp(): void
    {
        $this->context = new ListContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
    }

    private function assertTotalCountCallback(callable $totalCountCallback, int $expectedResult): void
    {
        $this->context->setTotalCountCallback($totalCountCallback);
        self::assertSame($totalCountCallback, $this->context->getTotalCountCallback());
        self::assertSame($totalCountCallback, $this->context->get('totalCountCallback'));
        self::assertSame($expectedResult, call_user_func($this->context->getTotalCountCallback()));
    }

    public function calculateTotalCount(): int
    {
        return 123;
    }

    public function testTotalCountCallback()
    {
        self::assertNull($this->context->getTotalCountCallback());

        $this->assertTotalCountCallback(
            function (): int {
                return 123;
            },
            123
        );

        $this->assertTotalCountCallback(
            new class() {
                public function __invoke(): int
                {
                    return 123;
                }
            },
            123
        );

        $this->assertTotalCountCallback([$this, 'calculateTotalCount'], 123);
    }
}
