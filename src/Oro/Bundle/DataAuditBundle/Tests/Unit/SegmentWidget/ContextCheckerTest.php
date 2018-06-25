<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\SegmentWidget;

use Oro\Bundle\DataAuditBundle\SegmentWidget\ContextChecker;

class ContextCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextChecker */
    protected $contextChecker;

    protected function setUp()
    {
        $this->contextChecker = new ContextChecker();
    }

    /** @dataProvider isApplicableDataProvider */
    public function testIsApplicable(array $context, $result)
    {
        $this->assertEquals($result, $this->contextChecker->isApplicableInContext($context));
    }

    /** @return array */
    public function isApplicableDataProvider()
    {
        return [
            'applicable'          => [[ContextChecker::DISABLED_PARAM => false], true],
            'applicable no param' => [[], true],
            'not applicable'      => [[ContextChecker::DISABLED_PARAM => true], false]
        ];
    }
}
