<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\SegmentWidget;

use Oro\Bundle\DataAuditBundle\SegmentWidget\ContextChecker;

class ContextCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextChecker */
    private $contextChecker;

    protected function setUp(): void
    {
        $this->contextChecker = new ContextChecker();
    }

    /**
     * @dataProvider isApplicableDataProvider
     */
    public function testIsApplicable(array $context, bool $result)
    {
        $this->assertEquals($result, $this->contextChecker->isApplicableInContext($context));
    }

    public function isApplicableDataProvider(): array
    {
        return [
            'applicable'          => [[ContextChecker::DISABLED_PARAM => false], true],
            'applicable no param' => [[], true],
            'not applicable'      => [[ContextChecker::DISABLED_PARAM => true], false]
        ];
    }
}
