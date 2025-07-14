<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Attribute;

use Oro\Bundle\NavigationBundle\Attribute\TitleTemplate;
use PHPUnit\Framework\TestCase;

class TitleTemplateTest extends TestCase
{
    private const TEST_VALUE = 'test annotation value';

    public function testGoodAnnotation(): void
    {
        $annotation = new TitleTemplate(self::TEST_VALUE);

        $this->assertEquals(self::TEST_VALUE, $annotation->getValue());
    }
}
