<?php
declare(strict_types=1);

namespace Oro\Component\Layout\Tests\Unit\Loader\Generator;

use Oro\Component\Layout\ExpressionLanguage\ExpressionValidator;
use Oro\Component\Layout\Loader\Generator\ConfigLayoutUpdateGenerator;
use Oro\Component\Layout\Loader\Generator\ConfigLayoutUpdateGeneratorExtensionInterface;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use PHPUnit\Framework\MockObject\MockObject;

class ConfigLayoutUpdateGeneratorTest extends \PHPUnit\Framework\TestCase
{
    protected ConfigLayoutUpdateGenerator $generator;

    protected function setUp(): void
    {
        $expressionValidator = $this->createMock(ExpressionValidator::class);
        $this->generator = new ConfigLayoutUpdateGenerator($expressionValidator);
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
