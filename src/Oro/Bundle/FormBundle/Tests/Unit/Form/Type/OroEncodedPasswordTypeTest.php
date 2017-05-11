<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEncodedPasswordType;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OroEncodedPasswordTypeTest extends FormIntegrationTestCase
{
    /** @var OroEncodedPasswordType */
    protected $formType;

    /** @var Mcrypt|\PHPUnit_Framework_MockObject_MockObject */
    protected $encryptor;

    protected function setUp()
    {
        parent::setUp();

        $this->encryptor = $this->createMock('Oro\Bundle\SecurityBundle\Encoder\Mcrypt');
        $this->formType = new OroEncodedPasswordType($this->encryptor);
    }

    public function testBuildForm()
    {
        // test encrypted password
        $encPassword = base64_encode('test');
        $this->encryptor->expects($this->once())
            ->method('encryptData')
            ->will($this->returnValue($encPassword));
        $form = $this->factory->create($this->formType);
        $form->submit('test');

        $this->assertEquals($encPassword, $form->getData());

        // test empty password with old password defined
        $form = $this->factory->create($this->formType, 'test');
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
        $form = $this->factory->create($this->formType, null, ['browser_autocomplete' => $state]);
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
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
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
