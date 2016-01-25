<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\ConfigExpression;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Asset\Packages;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Bundle\LayoutBundle\ConfigExpression\Asset;

class AssetTest extends \PHPUnit_Framework_TestCase
{
    /** @var Packages|\PHPUnit_Framework_MockObject_MockObject */
    protected $packages;

    /** @var Asset */
    protected $function;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->packages = $this->getMockBuilder('Symfony\Component\Asset\Packages')
            ->disableOriginalConstructor()
            ->getMock();

        $this->function = new Asset($this->packages);
        $this->function->setContextAccessor(new ContextAccessor());
    }

    /**
     * @param array $options
     * @param string|null $normalizedPath
     * @param string|null $expected
     *
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate($options, $normalizedPath, $expected)
    {
        if ($normalizedPath === null && $options) {
            $normalizedPath = $options[0];
        }
        $package = isset($options[1]) ? $options[1] : null;

        if ($normalizedPath) {
            $this->packages->expects($this->once())
                ->method('getUrl')
                ->with($normalizedPath, $package)
                ->willReturn($expected);
        } else {
            $this->packages->expects($this->never())
                ->method('getUrl');
        }

        if ($options) {
            $this->assertSame($this->function, $this->function->initialize($options));
        }
        $this->assertEquals($expected, $this->function->evaluate([]));
    }

    /**
     * @return array
     */
    public function evaluateDataProvider()
    {
        return [
            'with_path_only' => [
                'options' => ['path'],
                'normalizedPath' => null,
                'expected' => 'assets/path',
            ],
            'with_path_and_package_name' => [
                'options' => ['path', 'package'],
                'normalizedPath' => null,
                'expected' => 'assets/path',
            ],
            'with_full_path' => [
                'options' => ['@AcmeTestBundle/Resources/public/images/Picture.png'],
                'normalizedPath' => 'bundles/acmetest/images/Picture.png',
                'expected' => 'assets/bundles/acmetest/images/Picture.png',
            ],
            'with_non_bundle_path' => [
                'options' => ['@AcmeTest/Resources/public/images/Picture.png'],
                'normalizedPath' => null,
                'expected' => 'assets/@AcmeTest/Resources/public/images/Picture.png',
            ],
            'with_null_path' => [
                'options' => [],
                'normalizedPath' => null,
                'expected' => null,
            ],
        ];
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

        $this->packages->expects($this->never())
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

        $this->packages->expects($this->never())
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
     * @param array $options
     * @param string|null $message
     * @param array $expected
     *
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

    /**
     * @return array
     */
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
     * @param array $options
     * @param string|null $message
     * @param string $expected
     *
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
                    . '\'data.theme.icon\', [\'data\', \'theme\', \'icon\'], [false, false, false])'
                    . ', \'package\'])->setMessage(\'Test\')'
            ]
        ];
    }
}
