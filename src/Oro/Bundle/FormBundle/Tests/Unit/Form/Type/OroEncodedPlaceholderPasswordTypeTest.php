<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OroEncodedPlaceholderPasswordTypeTest extends FormIntegrationTestCase
{
    /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $crypter;

    /** @var OroEncodedPlaceholderPasswordType */
    private $formType;

    protected function setUp(): void
    {
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);

        $this->formType = new OroEncodedPlaceholderPasswordType($this->crypter);
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

    public function testBuildFormForNewPassword()
    {
        $pass = 'test';
        $passEncrypted = base64_encode($pass);

        $this->crypter->expects(self::once())
            ->method('encryptData')
            ->willReturn($passEncrypted);

        $form = $this->factory->create(OroEncodedPlaceholderPasswordType::class);
        $form->submit($pass);

        $this->assertEquals($passEncrypted, $form->getData());
    }

    public function testBuildFormForEmptyPasswordUseOldPass()
    {
        $pass = 'test';

        $form = $this->factory->create(OroEncodedPlaceholderPasswordType::class, $pass);
        $form->submit('');

        $this->assertEquals($pass, $form->getData());
    }

    public function testBuildView()
    {
        $pass = 'test';
        $passEncrypted = base64_encode($pass);
        $this->crypter->expects(self::once())
            ->method('decryptData')
            ->willReturn($pass);

        $form = $this->factory->create(OroEncodedPlaceholderPasswordType::class, $passEncrypted);

        $view = $form->createView();

        self::assertSame('****', $view->vars['value']);
    }

    public function testBuildViewWithAutocompleteAttribute()
    {
        $formDisabledAutocomplete = $this->factory->create(
            OroEncodedPlaceholderPasswordType::class,
            null,
            ['browser_autocomplete' => false]
        );
        $viewDisabledAutocomplete = $formDisabledAutocomplete->createView();
        $this->assertSame('new-password', $viewDisabledAutocomplete->vars['attr']['autocomplete']);

        $formEnabledAutocomplete = $this->factory->create(
            OroEncodedPlaceholderPasswordType::class,
            null,
            ['browser_autocomplete' => true]
        );
        $viewEnabledAutocomplete = $formEnabledAutocomplete->createView();
        $this->assertEmpty($viewEnabledAutocomplete->vars['attr']);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['browser_autocomplete' => false])
            ->willReturnSelf();
        $resolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with('browser_autocomplete', 'bool')
            ->willReturnSelf();

        $this->formType->configureOptions($resolver);
    }

    public function testGetParent()
    {
        self::assertSame(PasswordType::class, $this->formType->getParent());
    }

    public function testGetBlockPrefix()
    {
        self::assertSame('oro_encoded_placeholder_password', $this->formType->getBlockPrefix());
    }
}
