<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityPaginationBundle\Manager\MessageManager;
use Oro\Bundle\EntityPaginationBundle\Navigation\EntityPaginationNavigation;
use Oro\Bundle\EntityPaginationBundle\Storage\StorageDataCollector;
use Oro\Bundle\EntityPaginationBundle\Twig\EntityPaginationExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EntityPaginationExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $navigation;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $dataCollector;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $messageManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $requestStack;

    /** @var EntityPaginationExtension */
    protected $extension;

    protected function setUp()
    {
        $this->navigation =$this->getMockBuilder(EntityPaginationNavigation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataCollector = $this->getMockBuilder(StorageDataCollector::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManager = $this->getMockBuilder(MessageManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_entity_pagination.navigation', $this->navigation)
            ->add('oro_entity_pagination.storage.data_collector', $this->dataCollector)
            ->add('oro_entity_pagination.message_manager', $this->messageManager)
            ->add('request_stack', $this->requestStack)
            ->getContainer($this);

        $this->extension = new EntityPaginationExtension($container);
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

        $this->assertSame(
            $expected,
            self::callTwigFunction($this->extension, 'oro_entity_pagination_pager', [$entity, $scope])
        );
    }

    public function testCollectData()
    {
        $request = new Request();
        $scope = 'test';
        $result = true;

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->dataCollector->expects($this->once())
            ->method('collect')
            ->with($request, $scope)
            ->will($this->returnValue($result));

        $this->assertSame(
            $result,
            self::callTwigFunction($this->extension, 'oro_entity_pagination_collect_data', [$scope])
        );
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

        self::callTwigFunction($this->extension, 'oro_entity_pagination_show_info_message', [$entity, $scope]);
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
