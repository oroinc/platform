<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\ApiDocDataTypeConverter;

class ApiDocDataTypeConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ApiDocDataTypeConverter */
    private $converter;

    protected function setUp(): void
    {
        $this->converter = new ApiDocDataTypeConverter(
            ['type1' => 'doc_type1', 'type2' => 'doc_type2'],
            ['view1' => ['type2' => 'view1_doc_type2']]
        );
    }

    public function testForUndefinedDataType()
    {
        self::assertEquals('type10', $this->converter->convertDataType('type10', 'view1'));
        self::assertEquals('type10', $this->converter->convertDataType('type10', 'view10'));
    }

    public function testForViewSpecificDataType()
    {
        self::assertEquals('view1_doc_type2', $this->converter->convertDataType('type2', 'view1'));
    }

    public function testForDefaultDataType()
    {
        self::assertEquals('doc_type1', $this->converter->convertDataType('type1', 'view1'));
        self::assertEquals('doc_type2', $this->converter->convertDataType('type2', 'view2'));
    }
}
