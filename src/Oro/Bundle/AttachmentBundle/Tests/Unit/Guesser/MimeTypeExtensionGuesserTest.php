<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Guesser;

use Oro\Bundle\AttachmentBundle\Guesser\MimeTypeExtensionGuesser;

class MimeTypeExtensionGuesserTest extends \PHPUnit\Framework\TestCase
{
    /** @var MimeTypeExtensionGuesser */
    private $guesser;

    protected function setUp(): void
    {
        $this->guesser = new MimeTypeExtensionGuesser();
    }

    public function testIsGuesserSupported(): void
    {
        $this->assertTrue($this->guesser->isGuesserSupported());
    }

    public function testGuessMimeType(): void
    {
        $this->assertNull($this->guesser->guessMimeType(realpath(__DIR__ . '/../Fixtures/testFile/test.msg')));
    }

    /**
     * @dataProvider extensionDataProvider
     */
    public function testGetExtensions(string $mimeType, array $expectedExtensions): void
    {
        $this->assertEquals($expectedExtensions, $this->guesser->getExtensions($mimeType));
    }

    public function extensionDataProvider(): array
    {
        return [
            [
                'application/vnd.ms-outlook',
                ['msg'],
            ],
            [
                'nonExisting',
                [],
            ],
        ];
    }

    /**
     * @dataProvider mimeTypeDataProvider
     */
    public function testGetMimeTypes(string $extension, array $expectedMimeTypes): void
    {
        $this->assertEquals($expectedMimeTypes, $this->guesser->getMimeTypes($extension));
    }

    public function mimeTypeDataProvider(): array
    {
        return [
            [
                'msg',
                ['application/vnd.ms-outlook'],
            ],
            [
                'nonExisting',
                [],
            ],
        ];
    }
}
