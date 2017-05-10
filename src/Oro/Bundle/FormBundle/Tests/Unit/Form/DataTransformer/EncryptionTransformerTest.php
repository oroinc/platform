<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\FormBundle\Form\DataTransformer\EncryptionTransformer;

class EncryptionTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $encoder;

    /**
     * @var EncryptionTransformer
     */
    protected $encryptionTransformer;

    protected function setUp()
    {
        $this->encoder = $this->createMock(SymmetricCrypterInterface::class);
        $this->encryptionTransformer = new EncryptionTransformer($this->encoder);
    }

    public function testTransform()
    {
        $value = 'some_value';
        $encryptedValue = 'encrypted_some_value';

        $this->encoder->expects($this->once())
            ->method('decryptData')
            ->with($value)
            ->willReturn($encryptedValue);

        $actualValue = $this->encryptionTransformer->transform($value);

        $this->assertSame($encryptedValue, $actualValue);
    }

    public function testReverseTransform()
    {
        $value = 'some_value';
        $encryptedValue = 'encrypted_some_value';

        $this->encoder->expects($this->once())
            ->method('encryptData')
            ->with($encryptedValue)
            ->willReturn($value);

        $actualValue = $this->encryptionTransformer->reverseTransform($encryptedValue);

        $this->assertSame($value, $actualValue);
    }
}
