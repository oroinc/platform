<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Converter;

use Oro\Bundle\FormBundle\Form\Converter\TagDefinitionConverter;

class TagDefinitionConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var TagDefinitionConverter */
    private $converter;

    protected function setUp(): void
    {
        $this->converter = new TagDefinitionConverter();
    }

    /**
     * @dataProvider elementsDataProvider
     */
    public function testGetElements(string $allowedElements, array $expected)
    {
        $this->assertEquals(
            $expected,
            $this->converter->getElements($allowedElements)
        );
    }

    public function elementsDataProvider(): array
    {
        return [
            ['', []],
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
     * @dataProvider attributesDataProvider
     */
    public function testGetAttributes(string $allowedElements, array $expected)
    {
        $this->assertEquals(
            $expected,
            $this->converter->getAttributes($allowedElements)
        );
    }

    public function attributesDataProvider(): array
    {
        return [
            ['', []],
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
