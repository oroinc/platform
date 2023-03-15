<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Form\Model;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ImportExportBundle\Form\Model\ExportData;

class ExportDataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value)
    {
        $obj = new ExportData();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['processorAlias', 'test']
        ];
    }
}
