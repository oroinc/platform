<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Twig;

use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\ActionBundle\Provider\ButtonProvider;
use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Oro\Bundle\ActionBundle\Twig\OperationExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class OperationExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    const ROUTE = 'test_route';
    const REQUEST_URI = '/test/request/uri';

    /** @var \PHPUnit\Framework\MockObject\MockObject|RouteProviderInterface */
    protected $routeProvider;

    /** @var OperationExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContextHelper */
    protected $contextHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|OptionsHelper */
    protected $optionsHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ButtonProvider */
    protected $buttonProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ButtonSearchContextProvider */
    protected $buttonSearchContextProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->routeProvider = $this->createMock(RouteProviderInterface::class);
        $this->contextHelper = $this->getMockBuilder(ContextHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->optionsHelper = $this->getMockBuilder(OptionsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->buttonProvider = $this->getMockBuilder(ButtonProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->buttonSearchContextProvider = $this->getMockBuilder(ButtonSearchContextProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_action.provider.route', $this->routeProvider)
            ->add('oro_action.helper.context', $this->contextHelper)
            ->add('oro_action.helper.options', $this->optionsHelper)
            ->add('oro_action.provider.button', $this->buttonProvider)
            ->add('oro_action.provider.button_search_context', $this->buttonSearchContextProvider)
            ->getContainer($this);

        $this->extension = new OperationExtension($container);
    }

    protected function tearDown()
    {
        unset(
            $this->extension,
            $this->routeProvider,
            $this->contextHelper,
            $this->optionsHelper,
            $this->buttonProvider,
            $this->buttonSearchContextProvider
        );
    }

    public function testGetName()
    {
        $this->assertEquals(OperationExtension::NAME, $this->extension->getName());
    }

    /**
     * @dataProvider hasButtonsDataProvider
     *
     * @param bool $value
     */
    public function testHasButtons($value)
    {
        $this->contextHelper->expects($this->once())
            ->method('getContext')
            ->willReturn([]);

        $this->buttonSearchContextProvider
            ->expects($this->once())
            ->method('getButtonSearchContext')
            ->willReturn(new ButtonSearchContext());

        $this->buttonProvider->expects($this->once())->method('hasButtons')->willReturn($value);

        $this->assertEquals(
            $value,
            self::callTwigFunction($this->extension, 'oro_action_has_buttons', [[]])
        );
    }

    /**
     * @return array
     */
    public function hasButtonsDataProvider()
    {
        return [
            'has_buttons' => [true],
            'has_no_buttons' => [false],
        ];
    }
}
