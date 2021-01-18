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

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var IntegrationExtension */
    private $integrationExtension;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $container = self::getContainerBuilder()
            ->add('event_dispatcher', $this->dispatcher)
            ->getContainer($this);

        $this->integrationExtension = new IntegrationExtension($container);
    }

    public function testGetThemesShouldReturnDefaultThemeIfNoListenerIsRegistered()
    {
        $this->dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(LoadIntegrationThemesEvent::NAME)
            ->willReturn(false);
        $this->dispatcher->expects($this->never())
            ->method('dispatch');

        $themes = $this->integrationExtension->getThemes(new FormView());
        $this->assertEquals([IntegrationExtension::DEFAULT_THEME], $themes);
    }

    public function testGetThemesShouldReturnEventThemesIfListenerIsRegistered()
    {
        $themes = ['1', '2', '3'];

        $this->dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(LoadIntegrationThemesEvent::NAME)
            ->willReturn(true);
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(static::anything(), LoadIntegrationThemesEvent::NAME)
            ->will($this->returnCallback(function (LoadIntegrationThemesEvent $event, $eventName) use ($themes) {
                $event->setThemes($themes);
            }));

        $actualThemes = $this->integrationExtension->getThemes(new FormView());
        $this->assertEquals($themes, $actualThemes);
    }
}
