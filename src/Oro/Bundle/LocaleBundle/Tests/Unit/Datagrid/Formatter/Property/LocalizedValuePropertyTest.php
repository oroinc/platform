<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Datagrid\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyConfiguration;
use Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property\LocalizedValueProperty;

class LocalizedValuePropertyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LocalizedValueProperty
     */
    protected $property;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->property = new LocalizedValueProperty();
        $this->property->init(PropertyConfiguration::createNamed(LocalizedValueProperty::NAME, []));
    }

    public function testGetValue()
    {
        $record = new ResultRecord([LocalizedValueProperty::NAME => 'value1']);

        $this->assertEquals('value1', $this->property->getValue($record));
    }
}
