<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Event;

use Oro\Bundle\IntegrationBundle\Event\LoadIntegrationThemesEvent;
use Symfony\Component\Form\FormView;

class LoadIntegrationThemesEventTest extends \PHPUnit\Framework\TestCase
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
