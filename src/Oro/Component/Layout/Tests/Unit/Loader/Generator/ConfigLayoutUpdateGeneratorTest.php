<?php
declare(strict_types=1);

namespace Oro\Component\Layout\Tests\Unit\Loader\Generator;

use Oro\Component\Layout\Exception\SyntaxException;
use Oro\Component\Layout\ExpressionLanguage\ExpressionLanguageCacheWarmer;
use Oro\Component\Layout\Loader\Generator\ConfigLayoutUpdateGenerator;
use Oro\Component\Layout\Loader\Generator\ConfigLayoutUpdateGeneratorExtensionInterface;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConfigLayoutUpdateGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpressionLanguageCacheWarmer|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheWarmer;

    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $expressionValidator;

    /** @var ConfigLayoutUpdateGenerator */
    private $generator;

    protected function setUp(): void
    {
        $this->expressionValidator = $this->createMock(ValidatorInterface::class);
        $this->cacheWarmer = $this->createMock(ExpressionLanguageCacheWarmer::class);

        $this->generator = new ConfigLayoutUpdateGenerator($this->expressionValidator, $this->cacheWarmer);
    }

    public function testShouldCallExtensions(): void
    {
        $source = ['actions' => []];

        $extension = $this->createMock(ConfigLayoutUpdateGeneratorExtensionInterface::class);
        $this->generator->addExtension($extension);

        $extension->expects(self::once())
            ->method('prepare')
            ->with(
                new GeneratorData($source),
                self::isInstanceOf(VisitorCollection::class)
            );

        $this->generator->generate('testClassName', new GeneratorData($source));
    }

    /**
     * @dataProvider resourceDataProvider
     */
    public function testShouldValidateData(mixed $data, ?string $exception = null): void
    {
        if (null !== $exception) {
            $this->expectException(SyntaxException::class);
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

    public function testShouldValidateExpressionLanguageSyntax(): void
    {
        $constraintErrorMessage = '"Unclosed "[" around position 4 for expression `data[["foo"].bar()`."';
        $this->expectException(SyntaxException::class);
        $this->expectExceptionMessage(
            'Syntax error: ' . $constraintErrorMessage . ' at "actions.0.@setOption.optionValue"'
        );

        $violationsList = new ConstraintViolationList([
            new ConstraintViolation(
                $constraintErrorMessage,
                '',
                [],
                '',
                '',
                ''
            ),
        ]);
        $this->expressionValidator->expects($this->once())
            ->method('validate')
            ->willReturn($violationsList);

        $data = [
            'actions' => [
                [
                    '@setOption' => [
                        'id' => 'foo',
                        'optionName' => 'bar',
                        'optionValue' => '=data[["foo"].bar()',
                    ],
                ],
            ],
        ];
        $this->cacheWarmer->expects($this->never())
            ->method('collect');
        $this->generator->generate('testClassName', new GeneratorData($data));
    }

    public function testShouldCollectExpressions(): void
    {
        $violationsList = $this->createMock(ConstraintViolationListInterface::class);
        $violationsList->expects($this->exactly(5))
            ->method('count')
            ->willReturn(0);
        $this->expressionValidator->expects($this->exactly(5))
            ->method('validate')
            ->willReturn($violationsList);

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

    public function testForEmptyStringValue(): void
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
    public function testGenerate(): void
    {
        self::assertSame(
            <<<'CODE'
<?php

/**
 * Filename: testfilename.yml
 */
class testClassName implements Oro\Component\Layout\LayoutUpdateInterface
{
    public function updateLayout(
        Oro\Component\Layout\LayoutManipulatorInterface $layoutManipulator,
        Oro\Component\Layout\LayoutItemInterface $item,
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
