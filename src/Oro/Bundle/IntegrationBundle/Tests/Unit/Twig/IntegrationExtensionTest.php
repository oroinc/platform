<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Twig;

use Symfony\Component\Form\FormView;

use Oro\Bundle\IntegrationBundle\Event\LoadIntegrationThemesEvent;
use Oro\Bundle\IntegrationBundle\Twig\IntegrationExtension;

class IntegrationExtensionTest extends \PHPUnit_Framework_TestCase
{
    protected $dispatcher;
    protected $integrationExtension;

    public function setUp()
    {
        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->integrationExtension = new IntegrationExtension($this->dispatcher);
    }

    public function testGetThemesShouldReturnDefaultThemeIfNoListenerIsRegistered()
    {
        $this->dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(LoadIntegrationThemesEvent::NAME)
            ->will($this->returnValue(false));
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
            ->will($this->returnValue(true));
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(LoadIntegrationThemesEvent::NAME)
            ->will($this->returnCallback(function ($eventName, LoadIntegrationThemesEvent $event) use ($themes) {
                $event->setThemes($themes);
            }));

        $actualThemes = $this->integrationExtension->getThemes(new FormView());
        $this->assertEquals($themes, $actualThemes);
    }
}
