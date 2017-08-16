<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Processor\ContextInterface;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Util\Stub\ContextErrorUtilTraitStub;

class ContextErrorUtilTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContextErrorUtilTraitStub|\ReflectionClass
     */
    protected $util;

    public function setUp()
    {
        $this->util = new \ReflectionClass(ContextErrorUtilTraitStub::class);
    }

    /**
     * @param array $parameters
     * @param string $parent
     * @param string $expectedPointer
     * @dataProvider getBuildPointerProvider
     */
    public function testBuildPointer($parameters, $parent, $expectedPointer)
    {
        $method = $this->util->getMethod('buildPointer');
        $method->setAccessible(true);
        $stub = new ContextErrorUtilTraitStub();
        $pointer = $method->invoke($stub, $parameters, $parent);
        $this->assertEquals($expectedPointer, $pointer);
    }

    public function getBuildPointerProvider()
    {
        return [
            [
                ['prop1', 'prop2'],
                null,
                '/prop1/prop2',
            ],
            [
                ['prop1', 'prop2'],
                'parent',
                'parent/prop1/prop2',
            ],
            [
                ['prop1', 'prop2'],
                '/parent',
                '/parent/prop1/prop2',
            ],
            [
                [],
                'parent',
                'parent',
            ],
            [
                [],
                '',
                '',
            ],
        ];
    }

    /**
     * @param string $message
     * @param string|null $title
     * @dataProvider getAddErrorProvider
     */
    public function testAddError($message, $title = null)
    {
        $method = $this->util->getMethod('addError');
        $method->setAccessible(true);
        $stub = new ContextErrorUtilTraitStub();
        $context = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->once())
            ->method('addError')
            ->willReturnCallback(
                function ($error) use ($message, $title) {
                    $this->assertEquals($message, $error->getDetail());
                    $this->assertEquals('pointer', $error->getSource()->getPointer());
                    if ($title) {
                        $this->assertEquals($title, $error->getTitle());
                    } else {
                        $this->assertEquals(Constraint::REQUEST_DATA, $error->getTitle());
                    }
                }
            );
        $method->invoke($stub, 'pointer', $message, $context, $title);
    }

    public function getAddErrorProvider()
    {
        return [
            ['message'],
            ['message', 'title'],
        ];
    }
}
