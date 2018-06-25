<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Converter;

use Oro\Bundle\FormBundle\Form\Converter\TagDefinitionConverter;

class TagDefinitionConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TagDefinitionConverter
     */
    protected $converter;

    protected function setUp()
    {
        $this->converter = new TagDefinitionConverter();
    }

    /**
     * @param string $allowedElements
     * @param string $expected
     *
     * @dataProvider elementsDataProvider
     */
    public function testGetElements($allowedElements, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->converter->getElements($allowedElements)
        );
    }

    /**
     * @return array
     */
    public function elementsDataProvider()
    {
        return [
            [null, []],
            ['', []],
            ['p', ['p']],
            ['b/strong', ['b', 'strong']],
            ['@[class],b/ strong', ['b', 'strong']],
            ['@[class],@[style],b /strong', ['b', 'strong']],
            ['style[type="text/css"]', ['style']],
            ['style[type="text/css"|class|style]', ['style']],
            ['@[data-attribute|some-other="value-to-drop"],style[class|style],p[width|height]', ['style', 'p']],
            ['@some,[],style[class|style],p[width|height]', ['style', 'p']],
            ['@,[some stuff],style[ class |style],p[width|height]', ['style', 'p']],
            ['a[!href|target=_blank], b/p', ['a', 'b', 'p']],
            ['a[!href|target=_blank], a[!href|target=_blank], b/p', ['a', 'b', 'p']],
        ];
    }

    /**
     * @param string $allowedElements
     * @param string $expected
     *
     * @dataProvider attributesDataProvider
     */
    public function testGetAttributes($allowedElements, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->converter->getAttributes($allowedElements)
        );
    }

    /**
     * @return array
     */
    public function attributesDataProvider()
    {
        return [
            [null, []],
            ['', []],
            ['p', []],
            ['b/strong', []],
            ['@[class],b/ strong', ['*.class']],
            ['@[class],@[style],b /strong', ['*.class', '*.style']],
            ['style[type="text/css"]', ['style.type']],
            ['style[type="text/css"|class|style]', ['style.type', 'style.class', 'style.style']],
            [
                '@[data-attribute|some-other="value-to-drop"],style[class|style],p[width|height]',
                ['*.data-attribute', '*.some-other', 'style.class', 'style.style', 'p.width', 'p.height'],
            ],
            [
                '@some,[],style[class|style],p[width|height]',
                ['style.class', 'style.style', 'p.width', 'p.height'],
            ],
            [
                '@,[some stuff],style[ class |style],p[width|height]',
                ['style.class', 'style.style', 'p.width', 'p.height'],
            ],
            ['a[!href|target=_blank], b/p', ['a.href', 'a.target'],],
            ['a[!href|target=_blank], a[!href|target=_blank], b/p', ['a.href', 'a.target'],],
        ];
    }
}
