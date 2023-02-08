<?php
declare(strict_types=1);

namespace Oro\Component\Layout\Tests\Unit\Loader\Generator;

use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Generator\PhpLayoutUpdateGenerator;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use Oro\Component\Layout\Tests\Unit\Loader\Stubs\StubConditionVisitor;

class PhpLayoutUpdateGeneratorTest extends \PHPUnit\Framework\TestCase
{
    private PhpLayoutUpdateGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new PhpLayoutUpdateGenerator();
    }

    // @codingStandardsIgnoreStart
    /**
     * @dataProvider sourceCodeProvider
     */
    public function testGenerate(string $code)
    {
        $data = new GeneratorData($code, 'testfilename.php');

        self::assertSame(
            <<<'CODE'
<?php

/**
 * Filename: testfilename.php
 */
class testClassName implements Oro\Component\Layout\LayoutUpdateInterface
{
    public function updateLayout(
        Oro\Component\Layout\LayoutManipulatorInterface $layoutManipulator,
        Oro\Component\Layout\LayoutItemInterface $item,
    ) {
        echo 123;
    }
}

CODE
            ,
            $this->generator->generate('testClassName', $data)
        );
    }
    // @codingStandardsIgnoreEnd

    public function sourceCodeProvider(): array
    {
        return [
            'should take whole code given' => ['$code' => 'echo 123;'],
            'should remove open tag'       => ['$code' => "<?php\necho 123;"],
            'should remove short open tag' => ['$code' => "<?\necho 123;"],
            'should remove close tag'      => ['$code' => "<?\necho 123; ?>"],
        ];
    }

    // @codingStandardsIgnoreStart
    public function testShouldCompileConditions()
    {
        self::assertSame(
            <<<'CODE'
<?php

class testClassName implements Oro\Component\Layout\LayoutUpdateInterface
{
    public function updateLayout(
        Oro\Component\Layout\LayoutManipulatorInterface $layoutManipulator,
        Oro\Component\Layout\LayoutItemInterface $item,
    ) {
        if (true) {
            echo 123;
        }
    }
}

CODE
            ,
            $this->generator->generate(
                'testClassName',
                new GeneratorData('    echo 123;'), // current implementation does not track indentation levels
                new VisitorCollection([new StubConditionVisitor()])
            )
        );
    }
    // @codingStandardsIgnoreEnd
}
