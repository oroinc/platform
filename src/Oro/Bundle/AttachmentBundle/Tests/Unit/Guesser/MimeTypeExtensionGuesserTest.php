<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Guesser;

use Oro\Bundle\AttachmentBundle\Guesser\MimeTypeExtensionGuesser;

class MimeTypeExtensionGuesserTest extends \PHPUnit\Framework\TestCase
{
    /** @var MimeTypeExtensionGuesser */
    protected $guesser;

    protected function setUp(): void
    {
        $this->guesser = new MimeTypeExtensionGuesser();
    }

    /**
     * {@inheritdoc}
     */
    public function testIsGuesserSupported(): void
    {
        $this->assertTrue($this->guesser->isGuesserSupported());
    }

    /**
     * {@inheritdoc}
     */
    public function guessMimeType(): void
    {
        $this->assertNull($this->guesser->guessMimeType(realpath(__DIR__ . '/../Fixtures/testFile/test.msg')));
    }

    /**
     * @dataProvider extensionDataProvider
     *
     * @param string $mimeType
     * @param array $expectedExtensions
     */
    public function testGetExtensions(string $mimeType, array $expectedExtensions): void
    {
        $this->assertEquals($expectedExtensions, $this->guesser->getExtensions($mimeType));
    }

    /**
     * @return array
     */
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
     *
     * @param string $extension
     * @param array $expectedMimeTypes
     */
    public function testGetMimeTypes(string $extension, array $expectedMimeTypes): void
    {
        $this->assertEquals($expectedMimeTypes, $this->guesser->getMimeTypes($extension));
    }

    /**
     * @return array
     */
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
