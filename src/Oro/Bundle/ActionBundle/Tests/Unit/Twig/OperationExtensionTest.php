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

    private const ROUTE = 'test_route';
    private const REQUEST_URI = '/test/request/uri';

    /** @var RouteProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $routeProvider;

    /** @var ContextHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $contextHelper;

    /** @var OptionsHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $optionsHelper;

    /** @var ButtonProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $buttonProvider;

    /** @var ButtonSearchContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $buttonSearchContextProvider;

    /** @var OperationExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->routeProvider = $this->createMock(RouteProviderInterface::class);
        $this->contextHelper = $this->createMock(ContextHelper::class);
        $this->optionsHelper = $this->createMock(OptionsHelper::class);
        $this->buttonProvider = $this->createMock(ButtonProvider::class);
        $this->buttonSearchContextProvider = $this->createMock(ButtonSearchContextProvider::class);

        $container = self::getContainerBuilder()
            ->add('oro_action.provider.route', $this->routeProvider)
            ->add('oro_action.helper.context', $this->contextHelper)
            ->add('oro_action.helper.options', $this->optionsHelper)
            ->add('oro_action.provider.button', $this->buttonProvider)
            ->add('oro_action.provider.button_search_context', $this->buttonSearchContextProvider)
            ->getContainer($this);

        $this->extension = new OperationExtension($container);
    }

    /**
     * @dataProvider hasButtonsDataProvider
     */
    public function testHasButtons(bool $value)
    {
        $this->contextHelper->expects($this->once())
            ->method('getContext')
            ->willReturn([]);

        $this->buttonSearchContextProvider->expects($this->once())
            ->method('getButtonSearchContext')
            ->willReturn(new ButtonSearchContext());

        $this->buttonProvider->expects($this->once())
            ->method('hasButtons')
            ->willReturn($value);

        $this->assertEquals(
            $value,
            self::callTwigFunction($this->extension, 'oro_action_has_buttons', [[]])
        );
    }

    public function hasButtonsDataProvider(): array
    {
        return [
            'has_buttons' => [true],
            'has_no_buttons' => [false],
        ];
    }
}
