<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools\Imagine\Binary\Factory\GuessMimeType;

use Liip\ImagineBundle\Binary\MimeTypeGuesserInterface;
use Liip\ImagineBundle\Model\Binary;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\GuessMimeType\GuessMimeTypeByFileContentFactory;
use Symfony\Component\Mime\MimeTypesInterface;

class GuessMimeTypeByFileContentFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GuessMimeTypeByFileContentFactory
     */
    private $factory;

    /**
     * @var MimeTypeGuesserInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mimeTypeGuesser;

    /**
     * @var MimeTypesInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mimeTypes;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->mimeTypeGuesser = $this->createMock(MimeTypeGuesserInterface::class);
        $this->mimeTypes = $this->createMock(MimeTypesInterface::class);

        $this->factory = new GuessMimeTypeByFileContentFactory($this->mimeTypeGuesser, $this->mimeTypes);
    }

    /**
     * @param array $extensions
     * @param string|null $expectedFormat
     *
     * @dataProvider getCreateImagineBinaryDataProvider
     */
    public function testCreateImagineBinary(array $extensions, ?string $expectedFormat): void
    {
        $content = 'binary_content';
        $mimeType = 'image/jpeg';

        $this->mimeTypeGuesser
            ->method('guess')
            ->with($content)
            ->willReturn($mimeType);

        $this->mimeTypes
            ->method('getExtensions')
            ->with($mimeType)
            ->willReturn($extensions);

        static::assertEquals(
            new Binary($content, $mimeType, $expectedFormat),
            $this->factory->createImagineBinary($content)
        );
    }

    public function getCreateImagineBinaryDataProvider(): array
    {
        return [
            'no guessed extensions' => [
                'extensions' => [],
                'expectedFormat' => null
            ],
            'one guessed extension' => [
                'extensions' => ['png'],
                'expectedFormat' => 'png'
            ],
            'several guessed extension' => [
                'extensions' => ['jpeg', 'jpg', 'jpe'],
                'expectedFormat' => 'jpeg'
            ],
        ];
    }
}
