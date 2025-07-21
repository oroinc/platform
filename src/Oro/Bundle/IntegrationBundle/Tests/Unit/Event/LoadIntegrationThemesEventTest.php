<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Event;

use Oro\Bundle\IntegrationBundle\Event\LoadIntegrationThemesEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormView;

class LoadIntegrationThemesEventTest extends TestCase
{
    public function testConstructor(): void
    {
        $formView = new FormView();
        $themes = ['theme1'];

        $event = new LoadIntegrationThemesEvent($formView, $themes);
        $this->assertSame($formView, $event->getFormView());
        $this->assertEquals($themes, $event->getThemes());
    }

    public function testMethods(): void
    {
        $themes = ['theme1'];
        $event = new LoadIntegrationThemesEvent(new FormView());

        $event->setThemes($themes);
        $this->assertEquals($themes, $event->getThemes());

        $event->addTheme('theme2');
        $this->assertEquals(['theme1', 'theme2'], $event->getThemes());
    }
}
