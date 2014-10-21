<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Twig;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EntityPaginationBundle\Twig\EntityPaginationExtension;

class EntityPaginationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storage;

    /**
     * @var EntityPaginationExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->storage = $this->getMockBuilder('Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new EntityPaginationExtension($this->doctrineHelper, $this->storage);
    }

    public function testGetFunctions()
    {
        $expectedFunctions = [
            'oro_entity_pagination_previous' => [$this->extension, 'getPrevious'],
            'oro_entity_pagination_next'     => [$this->extension, 'getNext'],
            'oro_entity_pagination_pager'    => [$this->extension, 'getPager'],
        ];

        $functions = $this->extension->getFunctions();
        $this->assertSameSize($functions, $expectedFunctions);

        foreach ($functions as $function) {
            /** @var \Twig_SimpleFunction $function */
            $this->assertInstanceOf('\Twig_SimpleFunction', $function);
            $name = $function->getName();
            $this->assertArrayHasKey($name, $expectedFunctions);
            $this->assertEquals($expectedFunctions[$name], $function->getCallable());
        }
    }

    /**
     * @param mixed $expected
     * @param array $attributes
     * @param int|null $entityId
     * @param string|null $fieldName
     * @dataProvider getPreviousNextDataProvider
     */
    public function testGetPrevious($expected, array $attributes = null, $entityId = null, $fieldName = null)
    {
        $entity = new \stdClass();

        if (null !== $attributes) {
            $this->extension->setRequest(new Request([], [], $attributes));
        }

        $this->storage->expects($this->any())
            ->method('getPrevious')
            ->with($entity)
            ->will($this->returnValue($entityId));

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->with($entity)
            ->will($this->returnValue($fieldName));

        $this->assertSame($expected, $this->extension->getPrevious($entity));
    }

    /**
     * @param mixed $expected
     * @param array $attributes
     * @param int|null $entityId
     * @param string|null $fieldName
     * @dataProvider getPreviousNextDataProvider
     */
    public function testGetNext($expected, array $attributes = null, $entityId = null, $fieldName = null)
    {
        $entity = new \stdClass();

        if (null !== $attributes) {
            $this->extension->setRequest(new Request([], [], $attributes));
        }

        $this->storage->expects($this->any())
            ->method('getNext')
            ->with($entity)
            ->will($this->returnValue($entityId));

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->with($entity)
            ->will($this->returnValue($fieldName));

        $this->assertSame($expected, $this->extension->getNext($entity));
    }

    /**
     * @return array
     */
    public function getPreviousNextDataProvider()
    {
        return [
            'no request' => [
                'expected' => null,
            ],
            'no route' => [
                'expected' => null,
                'attributes' => [],
            ],
            'no route parameters' => [
                'expected' => null,
                'attributes' => ['_route' => 'test_route'],
            ],
            'no previous entity' => [
                'expected' => null,
                'attributes' => ['_route' => 'test_route', '_route_params' => ['test_id' => 2]],
            ],
            'no field name' => [
                'expected' => null,
                'attributes' => ['_route' => 'test_route', '_route_params' => ['test_id' => 2]],
                'entityId' => 1,
            ],
            'no identifier parameter' => [
                'expected' => null,
                'attributes' => ['_route' => 'test_route', '_route_params' => ['test_id' => 2]],
                'entityId' => 1,
                'fieldName' => 'id'
            ],
            'valid data' => [
                'expected' => ['route' => 'test_route', 'route_params' => ['id' => 1]],
                'attributes' => ['_route' => 'test_route', '_route_params' => ['id' => 2]],
                'entityId' => 1,
                'fieldName' => 'id'
            ],
        ];
    }

    /**
     * @param mixed $expected
     * @param int|null $totalCount
     * @param int|null $currentNumber
     * @dataProvider getPagerDataProvider
     */
    public function testGetPager($expected, $totalCount = null, $currentNumber = null)
    {
        $entity = new \stdClass();

        $this->storage->expects($this->any())
            ->method('getTotal')
            ->with($entity)
            ->will($this->returnValue($totalCount));
        $this->storage->expects($this->any())
            ->method('getCurrent')
            ->with($entity)
            ->will($this->returnValue($currentNumber));

        $this->assertSame($expected, $this->extension->getPager($entity));
    }

    /**
     * @return array
     */
    public function getPagerDataProvider()
    {
        return [
            'no total' => [
                'expected' => null,
            ],
            'no current' => [
                'expected' => null,
                'totalCount' => 100,
            ],
            'valid data' => [
                'expected' => ['total' => 100, 'current' => 25],
                'totalCount' => 100,
                'currentNumber' => 25,
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(EntityPaginationExtension::NAME, $this->extension->getName());
    }
}
