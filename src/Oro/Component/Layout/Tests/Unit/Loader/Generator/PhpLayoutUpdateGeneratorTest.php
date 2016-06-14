<?php

namespace Oro\Component\Layout\Tests\Unit\Loader\Generator;

use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Generator\PhpLayoutUpdateGenerator;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use Oro\Component\Layout\Tests\Unit\Loader\Stubs\StubConditionVisitor;

class PhpLayoutUpdateGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var PhpLayoutUpdateGenerator */
    protected $generator;

    protected function setUp()
    {
        $this->generator = new PhpLayoutUpdateGenerator();
    }

    protected function tearDown()
    {
        unset($this->generator);
    }

    // @codingStandardsIgnoreStart
    /**
     * @dataProvider sourceCodeProvider
     *
     * @param string $code
     */
    public function testGenerate($code)
    {
        $data = new GeneratorData($code, 'testfilename.php');

        $this->assertSame(
<<<CLASS
<?php

/**
 * Filename: testfilename.php
 */
class testClassName implements \Oro\Component\Layout\LayoutUpdateInterface
{
    public function updateLayout(\Oro\Component\Layout\LayoutManipulatorInterface \$layoutManipulator, \Oro\Component\Layout\LayoutItemInterface \$item)
    {
        echo 123;
    }
}
CLASS
            ,
            $this->generator->generate('testClassName', $data)
        );
    }
    // @codingStandardsIgnoreEnd

    public function sourceCodeProvider()
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
        $this->assertSame(
<<<CLASS
<?php

class testClassName implements \Oro\Component\Layout\LayoutUpdateInterface
{
    public function updateLayout(\Oro\Component\Layout\LayoutManipulatorInterface \$layoutManipulator, \Oro\Component\Layout\LayoutItemInterface \$item)
    {
        if (true) {
            echo 123;
        }
    }
}
CLASS
            ,
            $this->generator->generate(
                'testClassName',
                new GeneratorData('echo 123;'),
                new VisitorCollection([new StubConditionVisitor()])
            )
        );
    }
    // @codingStandardsIgnoreEnd
}
