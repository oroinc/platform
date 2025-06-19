<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\ApiDocDataTypeConverter;
use PHPUnit\Framework\TestCase;

class ApiDocDataTypeConverterTest extends TestCase
{
    private ApiDocDataTypeConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->converter = new ApiDocDataTypeConverter(
            ['type1' => 'doc_type1', 'type2' => 'doc_type2'],
            ['view1' => ['type2' => 'view1_doc_type2']]
        );
    }

    public function testForUndefinedDataType(): void
    {
        self::assertEquals('type10', $this->converter->convertDataType('type10', 'view1'));
        self::assertEquals('type10', $this->converter->convertDataType('type10', 'view10'));
    }

    public function testForViewSpecificDataType(): void
    {
        self::assertEquals('view1_doc_type2', $this->converter->convertDataType('type2', 'view1'));
    }

    public function testForDefaultDataType(): void
    {
        self::assertEquals('doc_type1', $this->converter->convertDataType('type1', 'view1'));
        self::assertEquals('doc_type2', $this->converter->convertDataType('type2', 'view2'));
    }
}
