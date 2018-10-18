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
    /**
     * @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $crypter;

    /**
     * @var OroEncodedPlaceholderPasswordType
     */
    private $formType;

    protected function setUp()
    {
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);

        $this->formType = new OroEncodedPlaceholderPasswordType($this->crypter);
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    OroEncodedPlaceholderPasswordType::class => $this->formType
                ],
                []
            ),
        ];
    }

    public function testBuildFormForNewPassword()
    {
        $pass = 'test';
        $passEncrypted = base64_encode($pass);

        $this->crypter->expects(static::once())
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
        $this->crypter->expects(static::once())
            ->method('decryptData')
            ->willReturn($pass);

        $form = $this->factory->create(OroEncodedPlaceholderPasswordType::class, $passEncrypted);

        $view = $form->createView();

        static::assertSame('****', $view->vars['value']);
    }

    /**
     * @dataProvider browserAutocompleteDataProvider
     *
     * @param bool $state
     * @param array $expected
     */
    public function testBuildViewWithAutocompleteAttribute($state, $expected)
    {
        $form = $this->factory->create(
            OroEncodedPlaceholderPasswordType::class,
            null,
            ['browser_autocomplete' => $state]
        );
        $view = $form->createView();

        static::assertArraySubset($expected, $view->vars['attr']);
    }

    /**
     * @return array
     */
    public function browserAutocompleteDataProvider()
    {
        return [
            'autocomplete disabled' => [false, ['autocomplete' => 'new-password']],
            'autocomplete enabled' => [true, []],
        ];
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit\Framework\MockObject\MockObject|OptionsResolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver
            ->expects($this->once())
            ->method('setDefaults')
            ->with(['browser_autocomplete' => false])
            ->will($this->returnSelf());

        $resolver
            ->expects($this->once())
            ->method('setAllowedTypes')
            ->with('browser_autocomplete', 'bool')
            ->will($this->returnSelf());

        $this->formType->configureOptions($resolver);
    }

    public function testGetParent()
    {
        static::assertSame(PasswordType::class, $this->formType->getParent());
    }

    public function testGetBlockPrefix()
    {
        static::assertSame('oro_encoded_placeholder_password', $this->formType->getBlockPrefix());
    }
}
