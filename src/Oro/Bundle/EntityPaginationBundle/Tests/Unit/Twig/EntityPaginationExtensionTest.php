<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Twig;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EntityPaginationBundle\Twig\EntityPaginationExtension;

class EntityPaginationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $navigation;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataCollector;

    /**
     * @var EntityPaginationExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->navigation =
            $this->getMockBuilder('Oro\Bundle\EntityPaginationBundle\Navigation\EntityPaginationNavigation')
                ->disableOriginalConstructor()
                ->getMock();

        $this->dataCollector = $this->getMockBuilder('Oro\Bundle\EntityPaginationBundle\Storage\StorageDataCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new EntityPaginationExtension($this->navigation, $this->dataCollector);
    }

    public function testGetFunctions()
    {
        $expectedFunctions = [
            'oro_entity_pagination_pager' => [$this->extension, 'getPager'],
            'oro_entity_pagination_collect_data' => [$this->extension, 'collectData'],
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
     * @param int|null $totalCount
     * @param int|null $currentNumber
     * @dataProvider getPagerDataProvider
     */
    public function testGetPager($expected, $totalCount = null, $currentNumber = null)
    {
        $entity = new \stdClass();

        $this->navigation->expects($this->any())
            ->method('getTotalCount')
            ->with($entity)
            ->will($this->returnValue($totalCount));
        $this->navigation->expects($this->any())
            ->method('getCurrentNumber')
            ->with($entity)
            ->will($this->returnValue($currentNumber));

        $this->assertSame($expected, $this->extension->getPager($entity));
    }

    public function testCollectData()
    {
        $request = new Request();
        $scope = 'test';
        $result = true;

        $this->dataCollector->expects($this->once())
            ->method('collect')
            ->with($request, $scope)
            ->will($this->returnValue($result));

        $this->extension->setRequest($request);

        $this->assertSame($result, $this->extension->collectData($scope));
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
