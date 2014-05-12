<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\TranslationBundle\Form\Type\AvailableTranslationsConfigurationType;

class AvailableTranslationsConfigurationTypeTest extends FormIntegrationTestCase
{
    /** @var  AvailableTranslationsConfigurationType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->formType = new AvailableTranslationsConfigurationType();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->formType);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_translation_available_translations', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('hidden', $this->formType->getParent());
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->formType);
        $this->assertContainsOnlyInstancesOf(
            'Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToJsonTransformer',
            $form->getConfig()->getModelTransformers()
        );
    }
}
