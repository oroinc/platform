<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\SecurityBundle\Form\DataTransformer\CryptedDataTransformer;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class CryptedDataTransformerTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    const ENCRYPTED_STRING = 'encryptedSample';
    const DECRYPTED_STRING = 'sample';

    /**
     * @var CryptedDataTransformer
     */
    private $transformer;

    /**
     * @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $crypter;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->transformer = new CryptedDataTransformer($this->crypter);

        $this->setUpLoggerMock($this->transformer);
    }

    /**
     * @dataProvider transformDataProvider
     *
     * @param string|null $value
     * @param string|null $expected
     */
    public function testTransform($value, $expected)
    {
        $this->crypter
            ->method('decryptData')
            ->with(self::ENCRYPTED_STRING)
            ->willReturn(self::DECRYPTED_STRING);

        $actual = $this->transformer->transform($value);

        static::assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        return [
            'when value is null' => [null, null],
            'when value is string, should be decrypted' => [self::ENCRYPTED_STRING, self::DECRYPTED_STRING],
        ];
    }

    public function testTransformWithException()
    {
        $this->crypter
            ->expects(static::once())
            ->method('decryptData')
            ->willThrowException(new \Exception());

        $this->assertLoggerErrorMethodCalled();

        $actual = $this->transformer->transform(self::ENCRYPTED_STRING);

        static::assertNull($actual);
    }

    /**
     * @dataProvider reverseTransformDataProvider
     *
     * @param string|null $value
     * @param string|null $expected
     */
    public function testReverseTransform($value, $expected)
    {
        $this->crypter
            ->method('encryptData')
            ->with(self::DECRYPTED_STRING)
            ->willReturn(self::ENCRYPTED_STRING);

        $actual = $this->transformer->reverseTransform($value);

        static::assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        return [
            'when value is null' => [null, null],
            'when value is string, should be encrypted' => [self::DECRYPTED_STRING, self::ENCRYPTED_STRING],
        ];
    }
}
