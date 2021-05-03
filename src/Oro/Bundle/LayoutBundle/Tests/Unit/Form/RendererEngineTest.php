<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Form;

use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;
use Oro\Component\Testing\ReflectionUtil;

abstract class RendererEngineTest extends \PHPUnit\Framework\TestCase
{
    public function testAddDefaultThemes()
    {
        $renderingEngine = $this->createRendererEngine();

        $actual = ReflectionUtil::getPropertyValue($renderingEngine, 'defaultThemes');
        $this->assertNotContains('newThemePath', $actual);

        $renderingEngine->addDefaultThemes('newThemePath');
        $actual = ReflectionUtil::getPropertyValue($renderingEngine, 'defaultThemes');
        $this->assertContains('newThemePath', $actual);

        $renderingEngine->addDefaultThemes(['newThemePath2', 'newThemePath3']);
        $actual = ReflectionUtil::getPropertyValue($renderingEngine, 'defaultThemes');
        $this->assertContains('newThemePath2', $actual);
        $this->assertContains('newThemePath3', $actual);
    }

    /**
     * @return FormRendererEngineInterface
     */
    abstract public function createRendererEngine();
}
