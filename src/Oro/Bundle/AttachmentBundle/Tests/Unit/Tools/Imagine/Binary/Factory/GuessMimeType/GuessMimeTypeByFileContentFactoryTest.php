<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools\Imagine\Binary\Factory\GuessMimeType;

use Liip\ImagineBundle\Binary\MimeTypeGuesserInterface;
use Liip\ImagineBundle\Model\Binary;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\GuessMimeType\GuessMimeTypeByFileContentFactory;
use Symfony\Component\Mime\MimeTypesInterface;

class GuessMimeTypeByFileContentFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var MimeTypeGuesserInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $mimeTypeGuesser;

    /** @var MimeTypesInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $mimeTypes;

    /** @var GuessMimeTypeByFileContentFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->mimeTypeGuesser = $this->createMock(MimeTypeGuesserInterface::class);
        $this->mimeTypes = $this->createMock(MimeTypesInterface::class);

        $this->factory = new GuessMimeTypeByFileContentFactory($this->mimeTypeGuesser, $this->mimeTypes);
    }

    /**
     * @dataProvider getCreateImagineBinaryDataProvider
     */
    public function testCreateImagineBinary(array $extensions, ?string $expectedFormat): void
    {
        $content = 'binary_content';
        $mimeType = 'image/jpeg';

        $this->mimeTypeGuesser->expects(self::any())
            ->method('guess')
            ->with($content)
            ->willReturn($mimeType);

        $this->mimeTypes->expects(self::any())
            ->method('getExtensions')
            ->with($mimeType)
            ->willReturn($extensions);

        self::assertEquals(
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
