<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Generator;

use Oro\Bundle\LayoutBundle\Layout\Generator\GeneratorData;
use Oro\Bundle\LayoutBundle\Layout\Generator\PhpLayoutUpdateGenerator;
use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionCollection;

use Oro\Bundle\LayoutBundle\Tests\Unit\Stubs\StubCondition;

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
        $data = new GeneratorData($code);
        $data->setFilename('testfilename.php');

        $this->assertSame(
<<<CLASS
<?php

class testClassName implements \Oro\Component\Layout\LayoutUpdateInterface
{
    public function updateLayout(\Oro\Component\Layout\LayoutManipulatorInterface \$layoutManipulator, \Oro\Component\Layout\LayoutItemInterface \$item)
    {
        // filename: testfilename.php
        echo 123;
    }
}
CLASS
            ,
            $this->generator->generate('testClassName', $data, new ConditionCollection())
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
                new ConditionCollection([new StubCondition()])
            )
        );
    }
    // @codingStandardsIgnoreEnd
}
