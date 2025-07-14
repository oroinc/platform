<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Datagrid\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyConfiguration;
use Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property\LocalizedValueProperty;
use PHPUnit\Framework\TestCase;

class LocalizedValuePropertyTest extends TestCase
{
    private LocalizedValueProperty $property;

    #[\Override]
    protected function setUp(): void
    {
        $this->property = new LocalizedValueProperty();
        $this->property->init(PropertyConfiguration::createNamed(LocalizedValueProperty::NAME, []));
    }

    public function testGetValue(): void
    {
        $record = new ResultRecord([LocalizedValueProperty::NAME => 'value1']);

        $this->assertEquals('value1', $this->property->getValue($record));
    }
}
