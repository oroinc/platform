<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEncodedPasswordType;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OroEncodedPasswordTypeTest extends FormIntegrationTestCase
{
    /** @var OroEncodedPasswordType */
    protected $formType;

    /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $encryptor;

    protected function setUp()
    {
        $this->encryptor = $this->createMock(SymmetricCrypterInterface::class);
        $this->formType = new OroEncodedPasswordType($this->encryptor);
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
                    OroEncodedPasswordType::class => $this->formType
                ],
                []
            ),
        ];
    }

    public function testBuildForm()
    {
        // test encrypted password
        $encPassword = base64_encode('test');
        $this->encryptor->expects($this->once())
            ->method('encryptData')
            ->will($this->returnValue($encPassword));
        $form = $this->factory->create(OroEncodedPasswordType::class);
        $form->submit('test');

        $this->assertEquals($encPassword, $form->getData());

        // test empty password with old password defined
        $form = $this->factory->create(OroEncodedPasswordType::class, 'test');
        $form->submit('');

        $this->assertEquals('test', $form->getData());
    }

    /**
     * @dataProvider browserAutocompleteDataProvider
     *
     * @param bool $state
     * @param array $expected
     */
    public function testBuildViewWithAutocompleteAttribute($state, $expected)
    {
        $form = $this->factory->create(OroEncodedPasswordType::class, null, ['browser_autocomplete' => $state]);
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

        $options = ['encode' => true, 'browser_autocomplete' => false];
        $resolver
            ->expects($this->once())
            ->method('setDefaults')
            ->with($options)
            ->will($this->returnSelf());

        $resolver
            ->expects($this->at(1))
            ->method('setAllowedTypes')
            ->with('encode', 'bool')
            ->will($this->returnSelf());

        $resolver
            ->expects($this->at(2))
            ->method('setAllowedTypes')
            ->with('browser_autocomplete', 'bool')
            ->will($this->returnSelf());

        $this->formType->configureOptions($resolver);
    }
}
