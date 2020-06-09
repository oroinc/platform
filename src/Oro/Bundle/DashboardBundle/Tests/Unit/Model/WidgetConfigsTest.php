<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Filter\WidgetConfigVisibilityFilter;
use Oro\Bundle\DashboardBundle\Model\ConfigProvider;
use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\DashboardBundle\Provider\ConfigValueProvider;
use Oro\Component\Config\Resolver\ResolverInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class WidgetConfigsTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $widgetRepository;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $valueProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configProvider;

    /** @var WidgetConfigs */
    private $widgetConfigs;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    protected $requestStack;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $resolver = $this->createMock(ResolverInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->valueProvider = $this->createMock(ConfigValueProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $widgetConfigVisibilityFilter = $this->createMock(WidgetConfigVisibilityFilter::class);

        $this->valueProvider->expects($this->any())
            ->method('getConvertedValue')
            ->willReturnCallback(
                function ($widgetConfig, $type, $value) {
                    return $value;
                }
            );

        $widgetConfigVisibilityFilter->expects($this->any())
            ->method('filterConfigs')
            ->will($this->returnArgument(0));

        $this->requestStack = new RequestStack();
        $this->widgetConfigs = new WidgetConfigs(
            $this->configProvider,
            $resolver,
            $this->em,
            $this->valueProvider,
            $this->translator,
            $this->eventDispatcher,
            $widgetConfigVisibilityFilter,
            $this->requestStack
        );

        $this->widgetRepository = $this->createMock(EntityRepository::class);
        $this->em->expects($this->any())
            ->method('getRepository')
            ->with('OroDashboardBundle:Widget')
            ->will($this->returnValue($this->widgetRepository));
    }

    public function testGetWidgetOptionsShouldReturnEmptyArrayIfRequestIsNull()
    {
        $this->assertEmpty($this->widgetConfigs->getWidgetOptions()->all());
    }

    public function testGetWidgetOptionsShouldReturnEmptyArrayIfThereIsNoWidgetIdInRequestQuery()
    {
        $request = new Request();
        $this->requestStack->push($request);

        $this->assertEmpty($this->widgetConfigs->getWidgetOptions()->all());
    }

    public function testGetWidgetOptionsShouldReturnOptionsOfWidget()
    {
        $request = new Request([
            '_widgetId' => 1,
        ]);
        $this->requestStack->push($request);

        $widget = new Widget();
        $widget->setName('test');
        $this->widgetRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->will($this->returnValue($widget));

        $options = ['k' => 'v', 'k2' => 'v2'];
        $widget->setOptions($options);

        $this->configProvider->expects($this->once())
            ->method('getWidgetConfig')
            ->willReturn(
                [
                    'configuration' => [
                        'k'  => ['type' => 'test'],
                        'k2' => ['type' => 'test'],
                    ]
                ]
            );

        $this->assertEquals(new WidgetOptionBag($options), $this->widgetConfigs->getWidgetOptions());
    }

    public function testGetWidgetOptionsShouldReturnOptionsOfWidgetSpecifiedAsArgument()
    {
        $request = new Request([
            '_widgetId' => 1,
        ]);
        $this->requestStack->push($request);

        $widget = new Widget();
        $widget->setName('test');
        $this->widgetRepository
            ->expects($this->once())
            ->method('find')
            ->with(2)
            ->will($this->returnValue($widget));

        $options = ['k' => 'v', 'k2' => 'v2'];
        $widget->setOptions($options);

        $this->configProvider->expects($this->once())
            ->method('getWidgetConfig')
            ->willReturn(
                [
                    'configuration' => [
                        'k'  => ['type' => 'test'],
                        'k2' => ['type' => 'test'],
                    ]
                ]
            );

        $this->assertEquals(new WidgetOptionBag($options), $this->widgetConfigs->getWidgetOptions(2));
    }
}
