<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroPlaceholderPasswordType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class OroPlaceholderPasswordTypeTest extends FormIntegrationTestCase
{
    /** @var OroPlaceholderPasswordType */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new OroPlaceholderPasswordType();
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType], [])
        ];
    }

    /**
     * @dataProvider buildViewProvider
     */
    public function testBuildView(
        ?string $defaultData,
        mixed $submitted,
        ?string $exceptedModel,
        string $exceptedView,
        bool $isError = false
    ) {
        $form = $this->factory->create(OroPlaceholderPasswordType::class);
        $form->setData($defaultData);

        if ($submitted !== false) {
            $form->submit($submitted);
        }

        $this->assertSame($exceptedModel, $form->getData(), 'Different model data');

        if ($isError) {
            $form->addError(new FormError('Test error'));
        }

        $view = $form->createView();
        $this->assertSame($exceptedView, $view->vars['value'], 'Different view value ($view->vars[\'value\'])');
    }

    public function buildViewProvider(): array
    {
        return [
            'empty form submit empty string' => [
                'defaultData' => '',
                'submitted' => '',
                'exceptedModel' => null,
                'exceptedView' => '',
            ],
            'form without submit' => [
                'defaultData' => 'original',
                'submitted' => false,
                'exceptedModel' => 'original',
                'exceptedView' => '********',
            ],
            'form with default data and submit empty string' => [
                'defaultData' => 'original',
                'submitted' => '',
                'exceptedModel' => null,
                'exceptedView' => '',
            ],
            'form without default data and submit new value' => [
                'defaultData' => '',
                'submitted' => 'submitted',
                'exceptedModel' => 'submitted',
                'exceptedView' => '*********',
            ],
            'form without default data and submit new value causes validate error' => [
                'defaultData' => '',
                'submitted' => 'submitted',
                'exceptedModel' => 'submitted',
                'exceptedView' => '',
                'isError' => true,
            ],
            'form with default data and submit new value' => [
                'defaultData' => 'original',
                'submitted' => 'submitted',
                'exceptedModel' => 'submitted',
                'exceptedView' => '*********',
            ],
            'form wit default data and submit new value causes validate error' => [
                'defaultData' => 'original',
                'submitted' => 'submitted',
                'exceptedModel' => 'submitted',
                'exceptedView' => '',
                'isError' => true,
            ],
            'form with default data and submit placeholder' => [
                'defaultData' => 'original',
                'submitted' => '********',
                'exceptedModel' => 'original',
                'exceptedView' => '********',
            ],
            'form with default data and submit part of placeholder' => [
                'defaultData' => 'original',
                'submitted' => '***',
                'exceptedModel' => '***',
                'exceptedView' => '***',
            ],
            'form with default data and submit placeholder causes validate error' => [
                'defaultData' => 'original',
                'submitted' => '********',
                'exceptedModel' => 'original',
                'exceptedView' => '********',
                'isError' => true,
            ],
        ];
    }

    /**
     * Test default state of autocomplete option
     */
    public function testAutocompleteMustBeDisabled()
    {
        $form = $this->factory->create(OroPlaceholderPasswordType::class);
        $view = $form->createView();
        // Autocomplete must be disabled by default
        $this->assertSame('off', $view->vars['attr']['autocomplete']);
    }

    /**
     * Test that attributes from developer's options are preserved
     */
    public function testAutocompleteShouldNotBeOverridenByDefault()
    {
        $options = ['attr' => ['autocomplete' => 'new-password']];
        $form = $this->factory->create(OroPlaceholderPasswordType::class, null, $options);
        $view = $form->createView();
        // Value from options must be used by default
        $this->assertSame('new-password', $view->vars['attr']['autocomplete']);
    }

    /**
     * Test that browser_autocomplete=true doesn't add autocomplete attributes
     */
    public function testBrowserAutocompleteOptionTrue()
    {
        $options = ['browser_autocomplete' => true];
        $form = $this->factory->create(OroPlaceholderPasswordType::class, null, $options);
        $view = $form->createView();
        // Use default browser's behaviour. Don't put any additional attributes
        $this->assertArrayNotHasKey('autocomplete', $view->vars['attr']);
    }

    /**
     * always_empty can not be changed. It always "true"
     */
    public function testAlwaysEmptyCanNotBeSetToFalse()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage(
            'The option "always_empty" with value false is invalid. Accepted values are: true.'
        );

        $this->factory->create(OroPlaceholderPasswordType::class, null, ['always_empty' => false]);
    }

    public function testGetParent()
    {
        self::assertSame(PasswordType::class, $this->formType->getParent());
    }

    public function testGetBlockPrefix()
    {
        self::assertSame('oro_placeholder_password', $this->formType->getBlockPrefix());
    }
}
