<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Controller;

use Oro\Bundle\TranslationBundle\Controller\Controller;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ControllerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Translator|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    private array $translations = [
        'jsmessages' => [
            'foo' => 'Foo',
            'bar' => 'Bar',
        ],
        'validators' => [
            'int' => 'Integer',
            'string' => 'string',
        ],
    ];

    protected function setUp(): void
    {
        $this->translator = $this->createMock(Translator::class);
        $this->twig = $this->createMock(Environment::class);
    }

    public function testConstructor(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Please provide valid twig template as third argument');

        new Controller($this->translator, $this->twig, '', []);
    }

    public function testIndexAction(): void
    {
        $content = 'CONTENT';

        $this->twig->expects(self::once())
            ->method('render')
            ->willReturn($content);

        $this->translator->expects(self::once())
            ->method('getTranslations')
            ->willReturn([]);

        $controller = new Controller(
            $this->translator,
            $this->twig,
            '@OroTranslation/Translation/translation.js.twig',
            []
        );

        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('getMimeType')
            ->with('json')
            ->willReturn('JSON');

        $response = $controller->indexAction($request, 'en');
        self::assertInstanceOf(Response::class, $response);
        self::assertEquals($content, $response->getContent());
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('JSON', $response->headers->get('Content-Type'));
    }

    /**
     * @dataProvider dataProviderRenderJsTranslationContent
     * @throws \JsonException
     */
    public function testRenderJsTranslationContent(array $params, array $expected): void
    {
        $this->twig->expects(self::any())
            ->method('render')
            ->willReturnCallback(function (string $name, array $context) {
                return json_encode($context['json'], JSON_THROW_ON_ERROR);
            });

        $this->translator
            ->expects(self::any())
            ->method('getTranslations')
            ->willReturnCallback(function ($domains) {
                return array_intersect_key($this->translations, array_flip($domains));
            });

        $controller = new Controller(
            $this->translator,
            $this->twig,
            '@OroTranslation/Translation/translation.js.twig',
            []
        );
        $result = call_user_func_array([$controller, 'renderJsTranslationContent'], $params);

        self::assertEquals(json_encode($expected, JSON_THROW_ON_ERROR), $result);
    }

    public function dataProviderRenderJsTranslationContent(): array
    {
        return [
            [
                [['jsmessages', 'validators'], 'fr'],
                [
                    'locale' => 'fr',
                    'defaultDomain' => 'jsmessages',
                    'translations' => [
                        'fr' => [
                            'jsmessages' => [
                                'foo' => 'Foo',
                                'bar' => 'Bar',
                            ],
                            'validators' => [
                                'int' => 'Integer',
                                'string' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
            [
                [['validators'], 'en', true],
                [
                    'locale' => 'en',
                    'defaultDomain' => 'validators',
                    'translations' => [
                        'en' => [
                            'validators' => [
                                'int' => 'Integer',
                                'string' => 'string',
                            ],
                        ],
                    ],
                    'debug' => true,
                ],
            ],
            [
                [[], 'ch', false],
                [
                    'locale' => 'ch',
                    'defaultDomain' => '',
                    'translations' => [
                        'ch' => []
                    ],
                ],
            ],
        ];
    }
}
