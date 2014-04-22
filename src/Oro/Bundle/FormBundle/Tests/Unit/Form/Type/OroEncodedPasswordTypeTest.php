<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\OroEncodedPasswordType;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

class OroEncodedPasswordTypeTest extends FormIntegrationTestCase
{
    /** @var OroEncodedPasswordType */
    protected $formType;

    /** @var Mcrypt|\PHPUnit_Framework_MockObject_MockObject */
    protected $encryptor;

    protected function setUp()
    {
        parent::setUp();

        $this->encryptor = $this->getMock('Oro\Bundle\SecurityBundle\Encoder\Mcrypt');
        $this->formType = new OroEncodedPasswordType($this->encryptor);
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->formType);
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
}
