<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Datagrid\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyConfiguration;
use Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property\FallbackProperty;

class FallbackPropertyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FallbackProperty
     */
    protected $property;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->property = new FallbackProperty();
        $this->property->init(
            PropertyConfiguration::createNamed(FallbackProperty::NAME, [])
        );
    }

    public function testGetValue()
    {
        $record = new ResultRecord([FallbackProperty::NAME => 'value1']);

        $this->assertEquals('value1', $this->property->getValue($record));
    }
}
