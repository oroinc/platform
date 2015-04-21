<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\ConfigExpression;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\UIBundle\ConfigExpression\Asset;

class AssetTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $assetsHelper;

    /** @var Asset */
    protected $function;

    protected function setUp()
    {
        $this->assetsHelper = $this->getMockBuilder('Symfony\Component\Templating\Helper\CoreAssetsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->function = new Asset($this->assetsHelper);
        $this->function->setContextAccessor(new ContextAccessor());
    }

    public function testEvaluateWithPathOnly()
    {
        $options        = ['path'];
        $context        = [];
        $expectedResult = 'assets/path';

        $this->assetsHelper->expects($this->once())
            ->method('getUrl')
            ->with($options[0], null)
            ->will($this->returnValue($expectedResult));

        $this->assertSame($this->function, $this->function->initialize($options));
        $this->assertEquals($expectedResult, $this->function->evaluate($context));
    }

    public function testEvaluateWithPathAndPackageName()
    {
        $options        = ['path', 'package'];
        $context        = [];
        $expectedResult = 'assets/path';

        $this->assetsHelper->expects($this->once())
            ->method('getUrl')
            ->with($options[0], $options[1])
            ->will($this->returnValue($expectedResult));

        $this->assertSame($this->function, $this->function->initialize($options));
        $this->assertEquals($expectedResult, $this->function->evaluate($context));
    }

    public function testEvaluateWithFullPath()
    {
        $options        = ['@AcmeTestBundle/Resources/public/images/Picture.png'];
        $normalizedPath = 'bundles/acmetest/images/Picture.png';
        $context        = [];
        $expectedResult = 'assets/bundles/acmetest/images/Picture.png';

        $this->assetsHelper->expects($this->once())
            ->method('getUrl')
            ->with($normalizedPath, null)
            ->will($this->returnValue($expectedResult));

        $this->assertSame($this->function, $this->function->initialize($options));
        $this->assertEquals($expectedResult, $this->function->evaluate($context));
    }

    public function testEvaluateWithNonBundlePath()
    {
        $options        = ['@AcmeTest/Resources/public/images/Picture.png'];
        $normalizedPath = '@AcmeTest/Resources/public/images/Picture.png';
        $context        = [];
        $expectedResult = 'assets/@AcmeTest/Resources/public/images/Picture.png';

        $this->assetsHelper->expects($this->once())
            ->method('getUrl')
            ->with($normalizedPath, null)
            ->will($this->returnValue($expectedResult));

        $this->assertSame($this->function, $this->function->initialize($options));
        $this->assertEquals($expectedResult, $this->function->evaluate($context));
    }

    public function testEvaluateWithNullPath()
    {
        $options = [new PropertyPath('path')];
        $context = ['path' => null];

        $this->assetsHelper->expects($this->never())
            ->method('getUrl');

        $this->assertSame($this->function, $this->function->initialize($options));
        $this->assertNull($this->function->evaluate($context));
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 1 or 2 elements, but 0 given.
     */
    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->function->initialize([]);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Path must not be empty.
     */
    public function testInitializeFailsWhenEmptyPath()
    {
        $this->function->initialize(['']);
    }

    public function testAddErrorForInvalidPathType()
    {
        $context = ['path' => 123, 'package' => 456];
        $options = [new PropertyPath('path'), new PropertyPath('package')];

        $this->function->initialize($options);
        $message = 'Error message.';
        $this->function->setMessage($message);

        $errors = new ArrayCollection();

        $this->assetsHelper->expects($this->never())
            ->method('getUrl');

        $this->assertSame($context['path'], $this->function->evaluate($context, $errors));

        $this->assertCount(1, $errors);
        $this->assertEquals(
            [
                'message'    => $message,
                'parameters' => [
                    '{{ path }}'        => 123,
                    '{{ packageName }}' => 456,
                    '{{ reason }}'      => 'Expected a string value for the path, got "integer".'
                ]
            ],
            $errors->get(0)
        );
    }

    public function testAddErrorForInvalidPackageNameType()
    {
        $context = ['path' => 'test', 'package' => 456];
        $options = [new PropertyPath('path'), new PropertyPath('package')];

        $this->function->initialize($options);
        $message = 'Error message.';
        $this->function->setMessage($message);

        $errors = new ArrayCollection();

        $this->assetsHelper->expects($this->never())
            ->method('getUrl');

        $this->assertSame($context['path'], $this->function->evaluate($context, $errors));

        $this->assertCount(1, $errors);
        $this->assertEquals(
            [
                'message'    => $message,
                'parameters' => [
                    '{{ path }}'        => 'test',
                    '{{ packageName }}' => 456,
                    '{{ reason }}'      => 'Expected null or a string value for the package name, got "integer".'
                ]
            ],
            $errors->get(0)
        );
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray($options, $message, $expected)
    {
        $this->function->initialize($options);
        if ($message !== null) {
            $this->function->setMessage($message);
        }
        $actual = $this->function->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function toArrayDataProvider()
    {
        return [
            [
                'options'  => ['path'],
                'message'  => null,
                'expected' => [
                    '@asset' => [
                        'parameters' => [
                            'path'
                        ]
                    ]
                ]
            ],
            [
                'options'  => [new PropertyPath('data.theme.icon'), 'package'],
                'message'  => 'Test',
                'expected' => [
                    '@asset' => [
                        'message'    => 'Test',
                        'parameters' => [
                            '$data.theme.icon',
                            'package'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile($options, $message, $expected)
    {
        $this->function->initialize($options);
        if ($message !== null) {
            $this->function->setMessage($message);
        }
        $actual = $this->function->compile('$factory');
        $this->assertEquals($expected, $actual);
    }

    public function compileDataProvider()
    {
        return [
            [
                'options'  => ['path'],
                'message'  => null,
                'expected' => '$factory->create(\'asset\', [\'path\'])'
            ],
            [
                'options'  => [new PropertyPath('data.theme.icon'), 'package'],
                'message'  => 'Test',
                'expected' => '$factory->create(\'asset\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath('
                    . '\'data.theme.icon\', [\'data\', \'theme\', \'icon\'])'
                    . ', \'package\'])->setMessage(\'Test\')'
            ]
        ];
    }
}
