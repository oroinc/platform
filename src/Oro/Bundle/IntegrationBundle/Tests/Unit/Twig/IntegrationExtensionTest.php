<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Twig;

use Oro\Bundle\IntegrationBundle\Event\LoadIntegrationThemesEvent;
use Oro\Bundle\IntegrationBundle\Twig\IntegrationExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormView;

class IntegrationExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var IntegrationExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $container = self::getContainerBuilder()
            ->add(EventDispatcherInterface::class, $this->dispatcher)
            ->getContainer($this);

        $this->extension = new IntegrationExtension($container);
    }

    public function testGetThemesShouldReturnDefaultThemeIfNoListenerIsRegistered(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(LoadIntegrationThemesEvent::NAME)
            ->willReturn(false);
        $this->dispatcher->expects($this->never())
            ->method('dispatch');

        $this->assertEquals(
            ['@OroIntegration/Form/fields.html.twig'],
            self::callTwigFunction($this->extension, 'oro_integration_themes', [new FormView()])
        );
    }

    public function testGetThemesShouldReturnEventThemesIfListenerIsRegistered(): void
    {
        $themes = ['1', '2', '3'];

        $this->dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(LoadIntegrationThemesEvent::NAME)
            ->willReturn(true);
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::anything(), LoadIntegrationThemesEvent::NAME)
            ->willReturnCallback(function (LoadIntegrationThemesEvent $event) use ($themes) {
                $event->setThemes($themes);

                return $event;
            });

        $this->assertEquals(
            $themes,
            self::callTwigFunction($this->extension, 'oro_integration_themes', [new FormView()])
        );
    }
}
