<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Extend;

use Oro\Bundle\EntityExtendBundle\Configuration\EntityExtendConfigurationProvider;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;

class FieldTypeHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var FieldTypeHelper */
    private $helper;

    protected function setUp(): void
    {
        $entityExtendConfigurationProvider = $this->createMock(EntityExtendConfigurationProvider::class);
        $entityExtendConfigurationProvider->expects(self::any())
            ->method('getUnderlyingTypes')
            ->willReturn(['enum' => 'manyToOne']);

        $this->helper = new FieldTypeHelper($entityExtendConfigurationProvider);
    }

    /**
     * @dataProvider getUnderlyingTypeProvider
     */
    public function testGetUnderlyingType(string $type, string $expectedType)
    {
        $this->assertEquals(
            $expectedType,
            $this->helper->getUnderlyingType($type)
        );
    }

    public function getUnderlyingTypeProvider(): array
    {
        return [
            ['ref-one', 'ref-one'],
            ['ref-many', 'ref-many'],
            ['manyToOne', 'manyToOne'],
            ['oneToMany', 'oneToMany'],
            ['manyToMany', 'manyToMany'],
            ['integer', 'integer'],
            ['enum', 'manyToOne']
        ];
    }

    /**
     * @dataProvider relationCHeckTestProvider
     */
    public function testIsRelation(string $fieldType, bool $expected)
    {
        $this->assertSame($expected, FieldTypeHelper::isRelation($fieldType));
    }

    public function relationCHeckTestProvider(): array
    {
        return [
            ['ref-one', true],
            ['ref-many', true],
            ['oneToMany', true],
            ['manyToOne', true],
            ['manyToMany', true],
            ['string', false],
            ['integer', false],
            ['text', false],
            ['array', false],
        ];
    }
}
