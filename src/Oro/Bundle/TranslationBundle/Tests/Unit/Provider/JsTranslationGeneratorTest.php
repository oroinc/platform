<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Provider\JsTranslationGenerator;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Twig\Environment;

class JsTranslationGeneratorTest extends \PHPUnit\Framework\TestCase
{
    private const TEMPLATE = '@OroTranslation/Translation/translation.js.twig';

    /** @var Translator|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(Translator::class);
        $this->twig = $this->createMock(Environment::class);
    }

    public function testGenerateJsTranslations(): void
    {
        $locale = 'en';
        $translations = [
            'jsmessages' => ['foo' => 'Foo', 'bar' => 'Bar'],
            'validators' => ['int' => 'Integer', 'string' => 'string']
        ];
        $expected = [
            'locale'        => $locale,
            'defaultDomain' => array_keys($translations)[0],
            'translations'  => [
                $locale => $translations
            ]
        ];

        $this->twig->expects(self::any())
            ->method('render')
            ->with(self::TEMPLATE)
            ->willReturnCallback(function (string $name, array $context) {
                return json_encode($context['json'], JSON_THROW_ON_ERROR);
            });

        $this->translator->expects(self::any())
            ->method('getTranslations')
            ->with(array_keys($translations), $locale)
            ->willReturn($translations);

        $generator = new JsTranslationGenerator(
            $this->translator,
            $this->twig,
            self::TEMPLATE,
            array_keys($translations)
        );
        $result = $generator->generateJsTranslations($locale);

        self::assertEquals(json_encode($expected, JSON_THROW_ON_ERROR), $result);
    }

    public function testGenerateJsTranslationsWhenDomainsAreEmpty(): void
    {
        $locale = 'en';
        $translations = [
            'jsmessages' => ['foo' => 'Foo', 'bar' => 'Bar'],
            'validators' => ['int' => 'Integer', 'string' => 'string']
        ];
        $expected = [
            'locale'        => $locale,
            'defaultDomain' => '',
            'translations'  => [
                $locale => $translations
            ]
        ];

        $this->twig->expects(self::any())
            ->method('render')
            ->with(self::TEMPLATE)
            ->willReturnCallback(function (string $name, array $context) {
                return json_encode($context['json'], JSON_THROW_ON_ERROR);
            });

        $this->translator->expects(self::any())
            ->method('getTranslations')
            ->with([], $locale)
            ->willReturn($translations);

        $generator = new JsTranslationGenerator(
            $this->translator,
            $this->twig,
            self::TEMPLATE,
            []
        );
        $result = $generator->generateJsTranslations($locale);

        self::assertEquals(json_encode($expected, JSON_THROW_ON_ERROR), $result);
    }
}
