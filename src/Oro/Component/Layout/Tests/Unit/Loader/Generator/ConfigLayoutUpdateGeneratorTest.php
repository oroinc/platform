<?php
declare(strict_types=1);

namespace Oro\Component\Layout\Tests\Unit\Loader\Generator;

use Oro\Component\Layout\ExpressionLanguage\ExpressionLanguageCacheWarmer;
use Oro\Component\Layout\ExpressionLanguage\ExpressionValidator;
use Oro\Component\Layout\Loader\Generator\ConfigLayoutUpdateGenerator;
use Oro\Component\Layout\Loader\Generator\ConfigLayoutUpdateGeneratorExtensionInterface;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use PHPUnit\Framework\MockObject\MockObject;

class ConfigLayoutUpdateGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigLayoutUpdateGenerator */
    protected $generator;

    /** @var ExpressionLanguageCacheWarmer|MockObject */
    protected $cacheWarmer;

    protected function setUp(): void
    {
        $expressionValidator = $this->createMock(ExpressionValidator::class);
        $this->cacheWarmer = $this->createMock(ExpressionLanguageCacheWarmer::class);
        $this->generator = new ConfigLayoutUpdateGenerator($expressionValidator);
        $this->generator->setCacheWarmer($this->cacheWarmer);
    }

    protected function tearDown(): void
    {
        unset($this->generator);
    }

    public function testShouldCallExtensions()
    {
        $source = ['actions' => []];

        /** @var ConfigLayoutUpdateGeneratorExtensionInterface|MockObject $extension */
        $extension = $this->createMock(ConfigLayoutUpdateGeneratorExtensionInterface::class);
        $this->generator->addExtension($extension);

        $extension->expects(static::once())
            ->method('prepare')
            ->with(
                new GeneratorData($source),
                static::isInstanceOf(VisitorCollection::class)
            );

        $this->generator->generate('testClassName', new GeneratorData($source));
    }

    /**
     * @dataProvider resourceDataProvider
     */
    public function testShouldValidateData($data, ?string $exception = null)
    {
        if (null !== $exception) {
            $this->expectException(\Oro\Component\Layout\Exception\SyntaxException::class);
            $this->expectExceptionMessage($exception);
        }

        $this->generator->generate('testClassName', new GeneratorData($data));
    }

    public function resourceDataProvider(): array
    {
        return [
            'invalid data'                                                   => [
                '$data'      => new \stdClass(),
                '$exception' => 'Syntax error: expected array with "actions" node at "."'
            ],
            'should contains actions'                                        => [
                '$data'      => [],
                '$exception' => 'Syntax error: expected array with "actions" node at "."'
            ],
            'should contains known actions'                                  => [
                '$data'      => [
                    'actions' => [
                        ['@addSuperPuper' => null]
                    ]
                ],
                '$exception' => 'Syntax error: unknown action "addSuperPuper", '
                    . 'should be one of LayoutManipulatorInterface methods at "actions.0"'
            ],
            'should contains array with action definition in actions'        => [
                '$data'      => [
                    'actions' => ['some string']
                ],
                '$exception' => 'Syntax error: expected array with action name as key at "actions.0"'
            ],
            'action name should start from @'                                => [
                '$data'      => [
                    'actions' => [
                        ['add' => null]
                    ]
                ],
                '$exception' => 'Syntax error: action name should start with "@" symbol,'
                    . ' current name "add" at "actions.0"'
            ],
            'known action proceed'                                           => [
                '$data'      => [
                    'actions' => [
                        ['@add' => null]
                    ]
                ],
                '$exception' => '"add" method requires at least 3 argument(s) to be passed, 1 given at "actions.0"'
            ],
        ];
    }

    public function testShouldCollectExpressions()
    {
        $data = [
            'actions' => [
                [
                    '@setOption' => [
                        'id' => 'foo',
                        'optionName' => 'bar',
                        'optionValue' => '=context["foo"].bar()',
                    ],
                    '@add' => [
                        'id' => 'foo',
                        'options' => [
                            'a' => '=bar',
                            'b' => 'regularText',
                            'c' => '=qux["a"].b(c)["d"]',
                            'd' => [
                                'nested' => [
                                    'option' => '=context["a"].call(data["b"])',
                                ],
                            ],
                        ],
                    ],
                    '@appendOption' => [
                        'id' => 'foo',
                        'optionName' => 'bar',
                        'optionValue' => '=context["baz"].qux()',
                    ],
                ],
            ],
        ];
        $this->cacheWarmer->expects($this->exactly(5))
            ->method('collect')
            ->withConsecutive(
                ['context["foo"].bar()'],
                ['bar'],
                ['qux["a"].b(c)["d"]'],
                ['context["a"].call(data["b"])'],
                ['context["baz"].qux()'],
            );
        $this->generator->generate('testClassName', new GeneratorData($data));
    }

    public function testForEmptyStringValue()
    {
        $data = [
            'actions' => [
                [
                    '@setOption' => [
                        'id' => 'foo',
                        'optionName' => 'bar',
                        'optionValue' => '',
                    ],
                ],
            ],
        ];
        $this->cacheWarmer->expects($this->never())
            ->method('collect');
        $this->generator->generate('testClassName', new GeneratorData($data));
    }

    // @codingStandardsIgnoreStart
    public function testGenerate()
    {
        static::assertSame(
            <<<'CODE'
<?php

/**
 * Filename: testfilename.yml
 */
class testClassName implements Oro\Component\Layout\LayoutUpdateInterface
{
    public function updateLayout(
        Oro\Component\Layout\LayoutManipulatorInterface $layoutManipulator,
        Oro\Component\Layout\LayoutItemInterface $item
    ) {
        $layoutManipulator->add( 'root', NULL, 'root' );
        $layoutManipulator->add( 'header', 'root', 'header' );
        $layoutManipulator->addAlias( 'header', 'header_alias' );
    }
}

CODE
            ,
            $this->generator->generate(
                'testClassName',
                new GeneratorData(
                    [
                        'actions' => [
                            [
                                '@add' => [
                                    'id'        => 'root',
                                    'parentId'  => null,
                                    'blockType' => 'root'
                                ]
                            ],
                            [
                                '@add' => [
                                    'id'        => 'header',
                                    'parentId'  => 'root',
                                    'blockType' => 'header'
                                ]
                            ],
                            [
                                '@addAlias' => [
                                    'alias' => 'header',
                                    'id'    => 'header_alias',
                                ]
                            ]
                        ]
                    ],
                    'testfilename.yml'
                )
            )
        );
    }
    // @codingStandardsIgnoreEnd
}
