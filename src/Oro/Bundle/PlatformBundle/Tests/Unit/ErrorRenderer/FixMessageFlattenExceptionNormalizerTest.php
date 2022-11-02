<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\ErrorRenderer;

use Oro\Bundle\PlatformBundle\ErrorRenderer\FixMessageFlattenExceptionNormalizer;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FixMessageFlattenExceptionNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var NormalizerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerNormalizer;

    /** @var FixMessageFlattenExceptionNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->innerNormalizer = $this->createMock(NormalizerInterface::class);

        $this->normalizer = new FixMessageFlattenExceptionNormalizer($this->innerNormalizer);
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(array $result, array $expectedResult): void
    {
        $data = FlattenException::create(new \Exception('some error'));
        $format = 'json';
        $context = ['key' => 'value'];

        $this->innerNormalizer->expects(self::once())
            ->method('normalize')
            ->with(self::identicalTo($data), $format, $context)
            ->willReturn($result);

        self::assertSame($expectedResult, $this->normalizer->normalize($data, $format, $context));
    }

    public function normalizeDataProvider(): array
    {
        return [
            'no code and message'                                  => [
                ['key' => 'value'],
                ['key' => 'value']
            ],
            'no message'                                           => [
                ['code' => 400],
                ['code' => 400]
            ],
            'no code'                                              => [
                ['message' => Response::$statusTexts[400]],
                ['message' => Response::$statusTexts[400]]
            ],
            'unknown code'                                         => [
                ['code' => 555, 'message' => 'error'],
                ['code' => 555]
            ],
            'message equals to code string representation'         => [
                ['code' => 400, 'message' => Response::$statusTexts[400]],
                ['code' => 400]
            ],
            'message does not equal to code string representation' => [
                ['code' => 400, 'message' => 'custom message'],
                ['code' => 400, 'message' => 'custom message']
            ]
        ];
    }

    public function testNormalizeWhenResultIsNotArray(): void
    {
        $data = FlattenException::create(new \Exception('some error'));
        $format = 'json';
        $context = ['key' => 'value'];
        $result = 'some result';

        $this->innerNormalizer->expects(self::once())
            ->method('normalize')
            ->with(self::identicalTo($data), $format, $context)
            ->willReturn($result);

        self::assertSame($result, $this->normalizer->normalize($data, $format, $context));
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization(bool $result): void
    {
        $data = FlattenException::create(new \Exception('some error'));
        $format = 'json';

        $this->innerNormalizer->expects(self::once())
            ->method('supportsNormalization')
            ->with(self::identicalTo($data), $format)
            ->willReturn($result);

        self::assertSame($result, $this->normalizer->supportsNormalization($data, $format));
    }

    public function supportsNormalizationDataProvider(): array
    {
        return [
            [false],
            [true]
        ];
    }
}
