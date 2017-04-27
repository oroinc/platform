<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroEncodedPlaceholderPasswordTypeTest extends FormIntegrationTestCase
{
    /**
     * @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $crypter;

    /**
     * @var OroEncodedPlaceholderPasswordType
     */
    private $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);

        $this->formType = new OroEncodedPlaceholderPasswordType($this->crypter);
    }

    public function testBuildFormForNewPassword()
    {
        $pass = 'test';
        $passEncrypted = base64_encode($pass);

        $this->crypter->expects(static::once())
            ->method('encryptData')
            ->willReturn($passEncrypted);

        $form = $this->factory->create($this->formType);
        $form->submit($pass);

        $this->assertEquals($passEncrypted, $form->getData());
    }

    public function testBuildFormForEmptyPasswordUseOldPass()
    {
        $pass = 'test';

        $form = $this->factory->create($this->formType, $pass);
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

        $form = $this->factory->create($this->formType, $passEncrypted);

        $view = $form->createView();

        static::assertSame('****', $view->vars['value']);
    }

    public function testGetParent()
    {
        static::assertSame('password', $this->formType->getParent());
    }

    public function testGetBlockPrefix()
    {
        static::assertSame('oro_encoded_placeholder_password', $this->formType->getBlockPrefix());
    }
}
