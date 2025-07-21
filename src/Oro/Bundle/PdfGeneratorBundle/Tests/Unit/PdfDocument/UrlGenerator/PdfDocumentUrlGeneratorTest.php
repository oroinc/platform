<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfDocument\UrlGenerator;

use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\UrlGenerator\PdfDocumentUrlGenerator;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PdfDocumentUrlGeneratorTest extends TestCase
{
    private const string UUID = '123e4567-e89b-12d3-a456-426614174000';

    private PdfDocumentUrlGenerator $generator;

    private MockObject&UrlGeneratorInterface $urlGenerator;

    private PdfDocument $pdfDocument;

    private string $routeName;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->pdfDocument = new PdfDocument();
        ReflectionUtil::setPropertyValue($this->pdfDocument, 'uuid', self::UUID);
        $this->routeName = 'pdf_document_route';

        $this->generator = new PdfDocumentUrlGenerator($this->urlGenerator, $this->routeName);
    }

    public function testGenerateUrlWithDefaultParameters(): void
    {
        $expectedUrl = '/pdf/document/' . self::UUID . '/download';

        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with(
                $this->routeName,
                ['uuid' => self::UUID, 'fileAction' => FileUrlProviderInterface::FILE_ACTION_DOWNLOAD],
                UrlGeneratorInterface::ABSOLUTE_PATH
            )
            ->willReturn($expectedUrl);

        $result = $this->generator->generateUrl($this->pdfDocument);

        self::assertSame($expectedUrl, $result);
    }

    public function testGenerateUrlWithCustomFileAction(): void
    {
        $fileAction = FileUrlProviderInterface::FILE_ACTION_GET;
        $expectedUrl = '/pdf/document/' . self::UUID . '/get';

        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with(
                $this->routeName,
                ['uuid' => self::UUID, 'fileAction' => $fileAction],
                UrlGeneratorInterface::ABSOLUTE_PATH
            )
            ->willReturn($expectedUrl);

        $result = $this->generator->generateUrl($this->pdfDocument, $fileAction);

        self::assertSame($expectedUrl, $result);
    }

    public function testGenerateUrlWithCustomReferenceType(): void
    {
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL;
        $expectedUrl = 'https://example.com/pdf/document/' . self::UUID . '/download';

        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with(
                $this->routeName,
                ['uuid' => self::UUID, 'fileAction' => FileUrlProviderInterface::FILE_ACTION_DOWNLOAD],
                $referenceType
            )
            ->willReturn($expectedUrl);

        $result = $this->generator->generateUrl(
            $this->pdfDocument,
            FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
            $referenceType
        );

        self::assertSame($expectedUrl, $result);
    }
}
