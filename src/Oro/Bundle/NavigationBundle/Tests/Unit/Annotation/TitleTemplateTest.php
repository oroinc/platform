<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Annotation;

use Oro\Bundle\NavigationBundle\Annotation\TitleTemplate;

class TitleTemplateTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_VALUE = 'test annotation value';

    public function testGoodAnnotation()
    {
        $annotation = new TitleTemplate(['value' => self::TEST_VALUE]);

        $this->assertEquals(self::TEST_VALUE, $annotation->getValue());
    }

    public function testBadAnnotation()
    {
        $this->expectException(\RuntimeException::class);
        $message = 'Unknown key "test" for annotation "@Oro\Bundle\NavigationBundle\Annotation\TitleTemplate".';
        $this->expectExceptionMessage($message);

        new TitleTemplate(['test' => self::TEST_VALUE]);
    }
}
