<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Twig;

use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\FormBundle\Form\Twig\DataBlockRenderer;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\ContainerRuntimeLoader;

class DataBlockRendererTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var Environment */
    private $environment;

    /** @var FormFactory */
    private $factory;

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var DataBlockRenderer */
    private $renderer;

    private array $testFormConfig = [
        0 => [
            'title'       => 'Second',
            'class'       => null,
            'subblocks'   => [
                0 => [
                    'code'        => 'text_3__subblock',
                    'title'       => null,
                    'data'        => ['text_3' => ''],
                    'description' => null,
                    'descriptionStyle' => null,
                    'useSpan'     => true,
                    'tooltip'     => null
                ],
            ],
            'description' => null
        ],
        1 => [
            'title'       => 'First Block',
            'class'       => null,
            'subblocks'   => [
                0 => [
                    'code'        => 'first',
                    'title'       => null,
                    'data'        => ['text_2' => ''],
                    'description' => null,
                    'descriptionStyle' => null,
                    'useSpan'     => true,
                    'tooltip'     => null
                ],
                1 => [
                    'code'        => 'second',
                    'title'       => 'Second SubBlock',
                    'data'        => ['text_1' => ''],
                    'description' => null,
                    'descriptionStyle' => null,
                    'useSpan'     => true,
                    'tooltip'     => null
                ],
            ],
            'description' => 'some desc'
        ],
        2  => [
            'title'       => 'Third',
            'class'       => null,
            'subblocks'   => [
                0 => [
                    'code'        => 'text_4__subblock',
                    'title'       => null,
                    'data'        => ['text_4' => ''],
                    'description' => null,
                    'descriptionStyle' => null,
                    'useSpan'     => true,
                    'tooltip'     => null
                ],
                1 => [
                    'code'        => 'first',
                    'title'       => null,
                    'data'        => ['text_5' => ''],
                    'description' => null,
                    'descriptionStyle' => null,
                    'useSpan'     => true,
                    'tooltip'     => null
                ],
            ],
            'description' => null
        ],
    ];

    protected function setUp(): void
    {
        $this->renderer = new DataBlockRenderer();

        $this->factory = Forms::createFormFactoryBuilder()
            ->addTypeExtension(new DataBlockExtension())
            ->getFormFactory();

        $this->container = $this->createMock(ContainerInterface::class);

        $this->environment = new Environment(new ArrayLoader());
        $this->environment->addExtension(new FormExtension());
        $this->environment->addRuntimeLoader(new ContainerRuntimeLoader($this->container));
    }

    public function testRender()
    {
        $options = [
            'block_config' =>
                [
                    'first'  => [
                        'priority'    => 1,
                        'title'       => 'First Block',
                        'subblocks'   => [
                            'first'  => [],
                            'second' => [
                                'title' => 'Second SubBlock'
                            ],
                        ],
                        'description' => 'some desc',
                        'descriptionStyle' => 'some desc style'
                    ],
                    'second' => [
                        'priority' => 2,
                    ]
                ]
        ];
        $builder = $this->factory->createNamedBuilder('test', FormType::class, null, $options);
        $builder->add('text_1', null, ['block' => 'first', 'subblock' => 'second']);
        $builder->add('text_2', null, ['block' => 'first']);
        $builder->add('text_3', null, ['block' => 'second']);
        $builder->add('text_4', null, ['block' => 'third']);
        $builder->add('text_5', null, ['block' => 'third', 'subblock' => 'first']);
        $builder->add('text_6', null);

        $formView = $builder->getForm()->createView();

        $formRenderer = $this->createMock(FormRenderer::class);
        $this->container->expects($this->any())
            ->method('has')
            ->with(FormRenderer::class)
            ->willReturn(true);
        $this->container->expects($this->any())
            ->method('get')
            ->with(FormRenderer::class)
            ->willReturn($formRenderer);
        $result = $this->renderer->render($this->environment, ['form' => $formView], $formView);

        $this->assertEquals($this->testFormConfig, $result);
    }
}
