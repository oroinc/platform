<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityPaginationBundle\Manager\MessageManager;
use Oro\Bundle\EntityPaginationBundle\Navigation\EntityPaginationNavigation;
use Oro\Bundle\EntityPaginationBundle\Storage\StorageDataCollector;
use Oro\Bundle\EntityPaginationBundle\Twig\EntityPaginationExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EntityPaginationExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private EntityPaginationNavigation&MockObject $navigation;
    private StorageDataCollector&MockObject $dataCollector;
    private MessageManager&MockObject $messageManager;
    private RequestStack&MockObject $requestStack;
    private EntityPaginationExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->navigation = $this->createMock(EntityPaginationNavigation::class);
        $this->dataCollector = $this->createMock(StorageDataCollector::class);
        $this->messageManager = $this->createMock(MessageManager::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $container = self::getContainerBuilder()
            ->add(EntityPaginationNavigation::class, $this->navigation)
            ->add(StorageDataCollector::class, $this->dataCollector)
            ->add(MessageManager::class, $this->messageManager)
            ->add(RequestStack::class, $this->requestStack)
            ->getContainer($this);

        $this->extension = new EntityPaginationExtension($container);
    }

    /**
     * @dataProvider getPagerDataProvider
     */
    public function testGetPager(?array $expected, ?int $totalCount = null, ?int $currentNumber = null): void
    {
        $entity = new \stdClass();
        $scope = 'test';

        $this->navigation->expects($this->any())
            ->method('getTotalCount')
            ->with($entity, $scope)
            ->willReturn($totalCount);
        $this->navigation->expects($this->any())
            ->method('getCurrentNumber')
            ->with($entity, $scope)
            ->willReturn($currentNumber);

        $this->assertSame(
            $expected,
            self::callTwigFunction($this->extension, 'oro_entity_pagination_pager', [$entity, $scope])
        );
    }

    public function testCollectData(): void
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
            ->willReturn($result);

        $this->assertSame(
            $result,
            self::callTwigFunction($this->extension, 'oro_entity_pagination_collect_data', [$scope])
        );
    }

    public function getPagerDataProvider(): array
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
     * @dataProvider showInfoMessageDataProvider
     */
    public function testShowInfoMessage(bool $hasMessage): void
    {
        $entity = new \stdClass();
        $scope = 'test';
        $message = $hasMessage ? 'Test message' : null;

        $this->messageManager->expects($this->once())
            ->method('getInfoMessage')
            ->with($entity, $scope)
            ->willReturn($message);

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

    public function showInfoMessageDataProvider(): array
    {
        return [
            'has message' => [true],
            'no message'  => [false],
        ];
    }
}
