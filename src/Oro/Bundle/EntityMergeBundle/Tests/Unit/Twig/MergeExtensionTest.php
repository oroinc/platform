<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model;

use Oro\Bundle\EntityMergeBundle\Twig\MergeExtension;

class MergeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MergeExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $accessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $renderer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    protected function setUp()
    {
        $this->accessor = $this->getMock('Oro\\Bundle\\EntityMergeBundle\\Model\\Accessor\\AccessorInterface');
        $this->renderer = $this->getMockBuilder('Oro\\Bundle\\EntityMergeBundle\\Twig\\MergeRenderer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMock('Symfony\\Component\\Translation\\TranslatorInterface');
        $this->extension = new MergeExtension($this->accessor, $this->renderer, $this->translator);
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(2, $functions);
        $this->assertInstanceOf('Twig_SimpleFunction', $functions[0]);
        $this->assertEquals('oro_entity_merge_render_field_value', $functions[0]->getName());
        $this->assertInstanceOf('Twig_SimpleFunction', $functions[1]);
        $this->assertEquals('oro_entity_merge_render_entity_label', $functions[1]->getName());
    }

    public function testGetFilters()
    {
        $filters = $this->extension->getFilters();
        $this->assertCount(1, $filters);
        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('oro_entity_merge_sort_fields', $filters[0]->getName());
    }

    public function testSortMergeFields()
    {
        $foo = $this->createFormView(array('name' => 'foo', 'label' => 'Foo'));
        $bar = $this->createFormView(array('name' => 'bar', 'label' => 'Bar'));
        $baz = $this->createFormView(array('name' => 'baz'));
        $actualFields = array($foo, $baz, $bar);
        $expectedFields = array($bar, $baz, $foo);

        $this->translator->expects($this->atLeastOnce())
            ->method('trans')
            ->will($this->returnArgument(0));

        $this->assertEquals($expectedFields, $this->extension->sortMergeFields($actualFields));
    }

    protected function createFormView(array $vars)
    {
        $result = $this->getMock('Symfony\\Component\\Form\\FormView');
        $result->vars = $vars;
        return $result;
    }
}
