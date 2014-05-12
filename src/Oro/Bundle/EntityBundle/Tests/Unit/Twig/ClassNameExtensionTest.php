<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityBundle\Twig\ClassNameExtension;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\__CG__\ItemStubProxy;

class ClassNameExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassNameExtension
     */
    protected $twigExtension;

    protected function setUp()
    {
        $this->twigExtension = new ClassNameExtension();
    }

    protected function tearDown()
    {
        unset($this->twigExtension);
    }

    public function testGetFunctions()
    {
        $functions = $this->twigExtension->getFunctions();
        $this->assertCount(1, $functions);

        /** @var \Twig_SimpleFunction $function */
        $function = current($functions);
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('oro_class_name', $function->getName());
        $this->assertEquals(array($this->twigExtension, 'getClassName'), $function->getCallable());
    }

    /**
     * @param string $expectedClass
     * @param mixed $object
     * @dataProvider getClassNameDataProvider
     */
    public function testGetClassName($expectedClass, $object)
    {
        $this->assertEquals($expectedClass, $this->twigExtension->getClassName($object));
    }

    public function getClassNameDataProvider()
    {
        return array(
            'not an object' => array(
                'expectedClass' => null,
                'object'        => 'string',
            ),
            'object' => array(
                'expectedClass' => 'Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub',
                'object'        => new ItemStub(),
            ),
            'proxy' => array(
                'expectedClass' => 'ItemStubProxy',
                'object'        => new ItemStubProxy(),
            ),
        );
    }

    public function testGetName()
    {
        $this->assertEquals(ClassNameExtension::NAME, $this->twigExtension->getName());
    }
}
