<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Event;

use Symfony\Component\Form\FormView;

use Oro\Bundle\IntegrationBundle\Event\LoadIntegrationThemesEvent;

class LoadIntegrationThemesEventTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $formView = new FormView();
        $themes = ['theme1'];

        $event = new LoadIntegrationThemesEvent($formView, $themes);
        $this->assertSame($formView, $event->getFormView());
        $this->assertEquals($themes, $event->getThemes());
    }

    public function testMethods()
    {
        $themes = ['theme1'];
        $event = new LoadIntegrationThemesEvent(new FormView());

        $event->setThemes($themes);
        $this->assertEquals($themes, $event->getThemes());

        $event->addTheme('theme2');
        $this->assertEquals(['theme1', 'theme2'], $event->getThemes());
    }
}
