<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Model;

use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class PageTemplateTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['label', 'Some label'],
            ['description', 'Some description'],
            ['screenshot', 'some/screenshot/path/img.jpg'],
            ['key', 'some_key'],
            ['routeName', 'some_route_name'],
            ['enabled', false]
        ];

        $entity = new PageTemplate(null, null, null);
        $this->assertPropertyAccessors($entity, $properties);
    }
}
