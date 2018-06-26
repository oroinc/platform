<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Controller;

use Oro\Bundle\TranslationBundle\Controller\Controller;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;

class ControllerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Translator|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var EngineInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $templating;

    /** @var array */
    protected $translations = [
        'jsmessages' => [
            'foo' => 'Foo',
            'bar' => 'Bar',
        ],
        'validators' => [
            'int' => 'Integer',
            'string' => 'string',
        ],
    ];

    protected function setUp()
    {
        $this->translator = $this->createMock(Translator::class);
        $this->templating = $this->createMock(EngineInterface::class);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Please provide valid twig template as third argument
     */
    public function testConstructor()
    {
        new Controller($this->translator, $this->templating, '', []);
    }

    public function testIndexAction()
    {
        $content = 'CONTENT';

        $this->templating->expects($this->once())
            ->method('render')
            ->will($this->returnValue($content));

        $this->translator->expects($this->once())
            ->method('getTranslations')
            ->will($this->returnValue([]));

        $controller = new Controller(
            $this->translator,
            $this->templating,
            'OroTranslationBundle:Translation:translation.js.twig',
            []
        );

        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('getMimeType')
            ->with('json')
            ->will($this->returnValue('JSON'));

        $response = $controller->indexAction($request, 'en');
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals($content, $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('JSON', $response->headers->get('Content-Type'));
    }

    /**
     * @dataProvider dataProviderRenderJsTranslationContent
     *
     * @param $params
     * @param $expected
     */
    public function testRenderJsTranslationContent($params, $expected)
    {
        $this->templating
            ->expects($this->any())
            ->method('render')
            ->will(
                $this->returnCallback(
                    function () {
                        $params = func_get_arg(1);
                        return $params['json'];
                    }
                )
            );


        $this->translator
            ->expects($this->any())
            ->method('getTranslations')
            ->will(
                $this->returnCallback(
                    function ($domains) {
                        return array_intersect_key($this->translations, array_flip($domains));
                    }
                )
            );

        $controller = new Controller(
            $this->translator,
            $this->templating,
            'OroTranslationBundle:Translation:translation.js.twig',
            []
        );
        $result = call_user_func_array([$controller, 'renderJsTranslationContent'], $params);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function dataProviderRenderJsTranslationContent()
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
