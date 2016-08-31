<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Form;

use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;

abstract class RendererEngineTest extends \PHPUnit_Framework_TestCase
{
    public function testAddDefaultThemes()
    {
        $renderingEngine = $this->createRendererEngine();

        $reflectionClass = new \ReflectionClass(get_class($renderingEngine));
        $property = $reflectionClass->getProperty('defaultThemes');
        $property->setAccessible(true);

        $actual = $property->getValue($renderingEngine);
        $this->assertNotContains('newThemePath', $actual);

        $renderingEngine->addDefaultThemes('newThemePath');
        $actual = $property->getValue($renderingEngine);
        $this->assertContains('newThemePath', $actual);

        $renderingEngine->addDefaultThemes(['newThemePath2', 'newThemePath3']);
        $actual = $property->getValue($renderingEngine);
        $this->assertContains('newThemePath2', $actual);
        $this->assertContains('newThemePath3', $actual);
    }

    /**
     * @return FormRendererEngineInterface
     */
    abstract public function createRendererEngine();
}
