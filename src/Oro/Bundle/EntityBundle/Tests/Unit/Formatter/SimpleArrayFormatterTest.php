<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Formatter;

use Oro\Bundle\EntityBundle\Formatter\SimpleArrayFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;

class SimpleArrayFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var SimpleArrayFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($id, $parameters, $domain) {
                return 'translated: ' . $id . ($domain ? ' (domain: ' . $domain . ')' : '');
            });

        $this->formatter = new SimpleArrayFormatter($translator);
    }

    /**
     * @dataProvider formatDataProvider
     */
    public function testFormat($value, array $arguments, $expectedValue)
    {
        self::assertEquals($expectedValue, $this->formatter->format($value, $arguments));
    }

    public function formatDataProvider(): array
    {
        return [
            'empty'                                  => [
                [],
                [],
                'translated: oro.entity.formatter.simple_array.default'
            ],
            'no arguments'                           => [
                ['val1', 'val2'],
                [],
                'val1, val2'
            ],
            'custom separator'                       => [
                ['val1', 'val2'],
                ['separator' => ';'],
                'val1;val2'
            ],
            'translatable'                           => [
                ['val1', 'val2'],
                ['translatable' => true],
                'translated: val1, translated: val2'
            ],
            'translatable, custom separator'         => [
                ['val1', 'val2'],
                ['translatable' => true, 'separator' => ' ; '],
                'translated: val1 ; translated: val2'
            ],
            'translatable with translation template' => [
                ['val1', 'val2'],
                ['translatable' => true, 'translation_template' => 'acme.%s.label'],
                'translated: acme.val1.label, translated: acme.val2.label'
            ],
            'translatable with translation domain'   => [
                ['val1', 'val2'],
                ['translatable' => true, 'translation_domain' => 'test'],
                'translated: val1 (domain: test), translated: val2 (domain: test)'
            ]
        ];
    }

    public function testGetDefaultValue()
    {
        self::assertEquals(
            'translated: oro.entity.formatter.simple_array.default',
            $this->formatter->getDefaultValue()
        );
    }
}
