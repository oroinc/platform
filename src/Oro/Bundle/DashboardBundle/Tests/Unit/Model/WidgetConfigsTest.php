<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\DashboardBundle\Filter\WidgetConfigVisibilityFilter;
use Oro\Bundle\DashboardBundle\Model\ConfigProvider;
use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\DashboardBundle\Provider\ConfigValueProvider;
use Oro\Bundle\SidebarBundle\Entity\Repository\WidgetRepository;
use Oro\Component\Config\Resolver\ResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class WidgetConfigsTest extends \PHPUnit\Framework\TestCase
{
    /** @var WidgetRepository|MockObject */
    private $widgetRepository;

    /** @var EntityManagerInterface|MockObject */
    private $em;

    /** @var ConfigValueProvider|MockObject */
    private $valueProvider;

    /** @var ConfigProvider|MockObject */
    protected $configProvider;

    private WidgetConfigs $widgetConfigs;

    /** @var TranslatorInterface|MockObject */
    protected $translator;

    /** @var EventDispatcherInterface|MockObject */
    protected $eventDispatcher;

    /** @var RequestStack|MockObject */
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

        $this->valueProvider->method('getConvertedValue')
            ->willReturnCallback(fn ($widgetConfig, $type, $value) => $value);

        $widgetConfigVisibilityFilter->method('filterConfigs')->willReturnArgument(0);

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
        $this->em->method('getRepository')->with('OroDashboardBundle:Widget')->willReturn($this->widgetRepository);
    }

    public function testGetWidgetOptionsShouldReturnEmptyOptionsBagIfRequestIsNull()
    {
        static::assertEmpty($this->widgetConfigs->getWidgetOptions()->all());
    }

    public function testGetWidgetOptionsShouldReturnEmptyOptionsBagIfThereIsNoWidgetIdInRequestQuery()
    {
        $this->requestStack->push(new Request());

        static::assertEmpty($this->widgetConfigs->getWidgetOptions()->all());
    }

    public function testGetWidgetOptionsShouldReturnEmptyOptionsBagIfWidgetDoesNotExist()
    {
        $this->requestStack->push(new Request(['_widgetId' => 1]));

        static::assertEmpty($this->widgetConfigs->getWidgetOptions()->all());
    }

    public function testGetWidgetOptionsShouldReturnOptionsOfWidget()
    {
        $request = new Request(['_widgetId' => 1,]);
        $this->requestStack->push($request);

        $widget = new Widget();
        $widget->setName('test');
        $this->widgetRepository->expects(static::once())
            ->method('find')
            ->with(1)
            ->willReturn($widget);

        $options = ['k' => 'v', 'k2' => 'v2'];
        $widget->setOptions($options);

        $this->configProvider->expects($this->once())
            ->method('getWidgetConfig')
            ->willReturn([
                'configuration' => [
                    'k'  => ['type' => 'test'],
                    'k2' => ['type' => 'test'],
                ]
            ]);

        static::assertEquals(new WidgetOptionBag($options), $this->widgetConfigs->getWidgetOptions());
    }

    public function testGetWidgetsShouldReturnOptionsFromLocalCacheOnSubsequentCalls()
    {
        $request = new Request(['_widgetId' => 1,]);
        $this->requestStack->push($request);

        $widget = (new Widget())->setName('test');
        $options = ['k' => 'v', 'k2' => 'v2'];
        $widget->setOptions($options);
        $this->widgetRepository->expects(static::once())
            ->method('find')
            ->with(1)
            ->willReturn($widget);

        $this->configProvider->expects(static::once())
            ->method('getWidgetConfig')
            ->willReturn([
                'configuration' => [
                    'k'  => ['type' => 'test'],
                    'k2' => ['type' => 'test'],
                ]
            ]);

        static::assertEquals(new WidgetOptionBag($options), $this->widgetConfigs->getWidgetOptions());
        static::assertEquals(new WidgetOptionBag($options), $this->widgetConfigs->getWidgetOptions());
    }

    public function testGetWidgetOptionsShouldReturnOptionsOfWidgetSpecifiedAsArgument()
    {
        $this->requestStack->push(new Request(['_widgetId' => 1,]));

        $widget = new Widget();
        $widget->setName('test');
        $this->widgetRepository->expects(static::once())
            ->method('find')
            ->with(2)
            ->willReturn($widget);

        $options = ['k' => 'v', 'k2' => 'v2'];
        $widget->setOptions($options);

        $this->configProvider->expects(static::once())
            ->method('getWidgetConfig')
            ->willReturn([
                'configuration' => [
                    'k'  => ['type' => 'test'],
                    'k2' => ['type' => 'test'],
                ]
            ]);

        static::assertEquals(new WidgetOptionBag($options), $this->widgetConfigs->getWidgetOptions(2));
    }

    public function testGetWidgetConfigShouldReturnNullIfConfigProviderReturnsNull()
    {
        $this->configProvider->method('getWidgetConfig')->willReturn(null);
        static::assertNull($this->widgetConfigs->getWidgetConfig('non-existent-widget'));
    }

    public function testGetWidgetConfigShouldPassThroughConfigProviderException()
    {
        $this->configProvider->method('getWidgetConfig')
            ->willThrowException(new InvalidConfigurationException('non-existent-widget'));
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage("Can't find configuration for: non-existent-widget");
        $this->widgetConfigs->getWidgetConfig('non-existent-widget');
    }
}
