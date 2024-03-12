<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Attribute;

use Oro\Bundle\NavigationBundle\Attribute\TitleTemplate;

class TitleTemplateTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_VALUE = 'test annotation value';

    public function testGoodAnnotation()
    {
        $annotation = new TitleTemplate(self::TEST_VALUE);

        $this->assertEquals(self::TEST_VALUE, $annotation->getValue());
    }
}
