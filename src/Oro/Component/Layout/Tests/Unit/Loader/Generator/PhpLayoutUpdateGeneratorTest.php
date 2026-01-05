<?php

declare(strict_types=1);

namespace Oro\Component\Layout\Tests\Unit\Loader\Generator;

use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Generator\PhpLayoutUpdateGenerator;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use Oro\Component\Layout\Tests\Unit\Loader\Stubs\StubConditionVisitor;
use PHPUnit\Framework\TestCase;

class PhpLayoutUpdateGeneratorTest extends TestCase
{
    private PhpLayoutUpdateGenerator $generator;

    #[\Override]
    protected function setUp(): void
    {
        $this->generator = new PhpLayoutUpdateGenerator();
    }

    // phpcs:disable
    /**
     * @dataProvider sourceCodeProvider
     */
    public function testGenerate(string $code): void
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
    // phpcs:enable

    public function sourceCodeProvider(): array
    {
        return [
            'should take whole code given' => ['$code' => 'echo 123;'],
            'should remove open tag'       => ['$code' => "<?php\necho 123;"],
            'should remove short open tag' => ['$code' => "<?\necho 123;"],
            'should remove close tag'      => ['$code' => "<?\necho 123; ?>"],
        ];
    }

    // phpcs:disable
    public function testShouldCompileConditions(): void
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
    // phpcs:enable
}
