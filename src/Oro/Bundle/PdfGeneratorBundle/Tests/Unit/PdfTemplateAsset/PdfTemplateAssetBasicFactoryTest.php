<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfTemplateAsset;

use GuzzleHttp\Psr7\Utils;
use Oro\Bundle\DistributionBundle\Provider\PublicDirectoryProvider;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetBasicFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PdfTemplateAssetBasicFactoryTest extends TestCase
{
    private PublicDirectoryProvider&MockObject $publicDirectoryProvider;

    private PdfTemplateAssetBasicFactory $factory;

    protected function setUp(): void
    {
        $this->publicDirectoryProvider = $this->createMock(PublicDirectoryProvider::class);

        $this->factory = new PdfTemplateAssetBasicFactory($this->publicDirectoryProvider);
    }

    public function testCreateFromPathWithAbsolutePath(): void
    {
        $filepath = '/tmp/pdf/sample.pdf';
        $asset = $this->factory->createFromPath($filepath);

        self::assertSame('__tmp__pdf__sample.pdf', $asset->getName());
        self::assertSame($filepath, $asset->getFilepath());
    }

    public function testCreateFromPathWithRelativePath(): void
    {
        $filepath = 'files/sample.pdf';
        $publicDir = '/var/www/public';
        $this->publicDirectoryProvider
            ->expects(self::once())
            ->method('getPublicDirectory')
            ->willReturn($publicDir);

        $asset = $this->factory->createFromPath($filepath);

        self::assertSame($publicDir . DIRECTORY_SEPARATOR . $filepath, $asset->getFilepath());
        self::assertSame('files__sample.pdf', $asset->getName());
    }

    public function testCreateFromPathWithUrl(): void
    {
        $filepath = 'http://example.com/files/sample.pdf';
        $this->publicDirectoryProvider
            ->expects(self::never())
            ->method('getPublicDirectory');

        $asset = $this->factory->createFromPath($filepath);

        self::assertSame($filepath, $asset->getFilepath());
        self::assertSame('__files__sample.pdf', $asset->getName());
    }

    public function testCreateFromRawData(): void
    {
        $data = 'sample pdf content';
        $name = 'sample.pdf';

        $asset = $this->factory->createFromRawData($data, $name);

        self::assertSame($name, $asset->getName());
        self::assertNull($asset->getFilepath());
    }

    public function testCreateFromStream(): void
    {
        $stream = Utils::streamFor('sample pdf content');
        $name = 'sample.pdf';

        $asset = $this->factory->createFromStream($stream, $name);

        self::assertSame($name, $asset->getName());
        self::assertNull($asset->getFilepath());
        self::assertSame($stream, $asset->getStream());
    }

    public function testIsApplicableAlwaysReturnsTrue(): void
    {
        self::assertTrue($this->factory->isApplicable(null, null, null, []));
    }
}
