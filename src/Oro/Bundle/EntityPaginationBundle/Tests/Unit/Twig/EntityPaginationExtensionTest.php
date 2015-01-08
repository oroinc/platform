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
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManager;

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

        $this->messageManager = $this->getMockBuilder('Oro\Bundle\EntityPaginationBundle\Manager\MessageManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new EntityPaginationExtension(
            $this->navigation,
            $this->dataCollector,
            $this->messageManager
        );
    }

    public function testGetFunctions()
    {
        $expectedFunctions = [
            'oro_entity_pagination_pager' => [$this->extension, 'getPager'],
            'oro_entity_pagination_collect_data' => [$this->extension, 'collectData'],
            'oro_entity_pagination_show_info_message' => [$this->extension, 'showInfoMessage'],
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
        $scope = 'test';

        $this->navigation->expects($this->any())
            ->method('getTotalCount')
            ->with($entity, $scope)
            ->will($this->returnValue($totalCount));
        $this->navigation->expects($this->any())
            ->method('getCurrentNumber')
            ->with($entity, $scope)
            ->will($this->returnValue($currentNumber));

        $this->assertSame($expected, $this->extension->getPager($entity, $scope));
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

    /**
     * @param bool $hasMessage
     * @dataProvider showInfoMessageDataProvider
     */
    public function testShowInfoMessage($hasMessage)
    {
        $entity = new \stdClass();
        $scope = 'test';
        $message = $hasMessage ? 'Test message' : null;

        $this->messageManager->expects($this->once())
            ->method('getInfoMessage')
            ->with($entity, $scope)
            ->will($this->returnValue($message));

        if ($hasMessage) {
            $this->messageManager->expects($this->once())
                ->method('addFlashMessage')
                ->with('info', $message);
        } else {
            $this->messageManager->expects($this->never())
                ->method('addFlashMessage');
        }

        $this->extension->showInfoMessage($entity, $scope);
    }

    public function showInfoMessageDataProvider()
    {
        return [
            'has message' => [true],
            'no message'  => [false],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(EntityPaginationExtension::NAME, $this->extension->getName());
    }
}
