<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\HTMLAttributeType;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

class HTMLAttributeTypeTest extends AttributeTypeTestCase
{
    /** @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $htmlTagHelper;

    protected function setUp()
    {
        parent::setUp();

        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->htmlTagHelper->expects($this->any())
            ->method('stripTags')
            ->willReturnCallback(
                function ($value) {
                    return $value . ' stripped';
                }
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function getAttributeType()
    {
        return new HTMLAttributeType($this->htmlTagHelper);
    }

    public function testGetType()
    {
        $this->assertEquals('html_escaped', $this->getAttributeType()->getType());
    }

    /**
     * {@inheritdoc}
     */
    public function configurationMethodsDataProvider()
    {
        yield [
            'isSearchable' => true,
            'isFilterable' => true,
            'isSortable' => false
        ];
    }

    public function testGetSearchableValue()
    {
        $this->assertSame(
            'text stripped',
            $this->getAttributeType()->getSearchableValue($this->attribute, 'text', $this->localization)
        );
    }

    public function testGetFilterableValue()
    {
        $this->assertSame(
            'text stripped',
            $this->getAttributeType()->getFilterableValue($this->attribute, 'text', $this->localization)
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not supported
     */
    public function testGetSortableValue()
    {
        $this->getAttributeType()->getSortableValue($this->attribute, 'text', $this->localization);
    }
}
