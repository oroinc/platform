<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Placeholder\Filter;

use Oro\Bundle\UIBundle\Placeholder\Filter\AttributeInstanceOf;

class AttributeInstanceOfTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    /**
     * @var AttributeInstanceOf
     */
    protected $filter;

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->filter = new AttributeInstanceOf($this->container);
    }

    /**
     * @dataProvider filterDataProvider
     */
    public function testFilter($actual, $variables, $expected, $containerParametersValueMap = array())
    {
        if ($containerParametersValueMap) {
            $this->container->expects($this->exactly(count($containerParametersValueMap)))
                ->method('getParameter')
                ->will($this->returnValueMap($containerParametersValueMap));
        }

        $this->assertEquals(
            $expected,
            $this->filter->filter($actual, $variables)
        );
    }

    public function filterDataProvider()
    {
        $entity = $this->getMock('Foo_Entity');

        return array(
            'empty_attribute' => array(
                'actual' => array(
                    array('template' => 'foo'),
                    array('template' => 'bar'),
                    array('template' => 'baz'),
                ),
                'variables' => array(),
                'expected' => array(
                    array('template' => 'foo'),
                    array('template' => 'bar'),
                    array('template' => 'baz'),
                ),
            ),
            'attribute' => array(
                'actual' => array(
                    $foo = array(
                        'template' => 'foo',
                        AttributeInstanceOf::ATTRIBUTE_NAME => array('entity', get_class($entity))
                    ),
                    array(
                        'template' => 'bar',
                        AttributeInstanceOf::ATTRIBUTE_NAME => array('entity', 'stdClass')
                    ),
                    array('template' => 'baz'),
                ),
                'variables' => array('entity' => $entity),
                'expected' => array(
                    $foo,
                    array('template' => 'baz'),
                ),
            ),
            'container_parameter' => array(
                'actual' => array(
                    $foo = array(
                        'template' => 'foo',
                        AttributeInstanceOf::ATTRIBUTE_NAME => array(
                            'entity',
                            '%container_param%',
                        )
                    ),
                    array('template' => 'bar'),
                    array('template' => 'baz'),
                ),
                'variables' => array('entity' => $entity),
                'expected' => array(
                    $foo,
                    array('template' => 'bar'),
                    array('template' => 'baz'),
                ),
                'containerParametersValueMap' => array(
                    array('%container_param%', get_class($entity)),
                )
            ),
        );
    }
}
