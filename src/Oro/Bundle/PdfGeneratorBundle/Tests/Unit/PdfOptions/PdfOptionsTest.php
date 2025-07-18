<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfOptions;

use Oro\Bundle\PdfGeneratorBundle\Exception\PdfOptionsException;
use Oro\Bundle\PdfGeneratorBundle\PdfOptions\PdfOptions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class PdfOptionsTest extends TestCase
{
    private OptionsResolver $optionsResolver;

    protected function setUp(): void
    {
        $this->optionsResolver = new OptionsResolver();
        $this->optionsResolver->setDefined(['scale', 'landscape']);
    }

    public function testGetPreset(): void
    {
        $pdfOptions = new PdfOptions([], $this->optionsResolver, 'default_preset');
        self::assertSame('default_preset', $pdfOptions->getPreset());
    }

    public function testResolveSuccessfully(): void
    {
        $options = ['landscape' => true, 'scale' => 0.5];
        $pdfOptions = new PdfOptions($options, $this->optionsResolver);
        $resolvedPdfOptions = $pdfOptions->resolve();

        self::assertTrue($resolvedPdfOptions->isResolved());
        self::assertSame($options, $resolvedPdfOptions->toArray());
    }

    public function testResolveThrowsExceptionOnInvalidOption(): void
    {
        $pdfOptions = new PdfOptions(['invalid_option' => 'value'], $this->optionsResolver);

        $this->expectException(PdfOptionsException::class);
        $this->expectExceptionMessage(
            'Failed to resolve PDF options: The option "invalid_option" does not exist.' .
            ' Defined options are: "landscape", "scale".'
        );

        $pdfOptions->resolve();
    }

    public function testIsDefined(): void
    {
        $pdfOptions = new PdfOptions([], $this->optionsResolver);
        self::assertTrue($pdfOptions->isDefined('scale'));
        self::assertFalse($pdfOptions->isDefined('invalid_option'));
    }

    public function testOffsetExists(): void
    {
        $pdfOptions = new PdfOptions(['scale' => 0.5], $this->optionsResolver);
        self::assertTrue($pdfOptions->offsetExists('scale'));
        self::assertFalse($pdfOptions->offsetExists('landscape'));
    }

    public function testOffsetGet(): void
    {
        $pdfOptions = new PdfOptions(['scale' => 0.5], $this->optionsResolver);
        self::assertSame(0.5, $pdfOptions['scale']);
    }

    public function testOffsetGetThrowsExceptionForUndefinedOption(): void
    {
        $pdfOptions = new PdfOptions([], $this->optionsResolver);

        $this->expectException(PdfOptionsException::class);
        $this->expectExceptionMessage('Option invalid_option is not defined. Defined options are: scale, landscape');

        $pdfOptions->offsetGet('invalid_option');
    }

    public function testOffsetSet(): void
    {
        $pdfOptions = new PdfOptions([], $this->optionsResolver);
        $pdfOptions['scale'] = 0.5;

        self::assertSame(0.5, $pdfOptions['scale']);
    }

    public function testOffsetSetThrowsExceptionForUndefinedOption(): void
    {
        $pdfOptions = new PdfOptions([], $this->optionsResolver);

        $this->expectException(PdfOptionsException::class);
        $this->expectExceptionMessage('Option invalid_option is not defined. Defined options are: scale, landscape');

        $pdfOptions->offsetSet('invalid_option', 20);
    }

    public function testOffsetSetThrowsExceptionIfAlreadyResolved(): void
    {
        $pdfOptions = new PdfOptions(['scale' => 0.5], $this->optionsResolver);
        $pdfOptions->resolve();

        $this->expectException(PdfOptionsException::class);
        $this->expectExceptionMessage('PDF options are already resolved and cannot be changed');

        $pdfOptions['scale'] = 0.5;
    }

    public function testOffsetUnset(): void
    {
        $pdfOptions = new PdfOptions(['scale' => 0.5], $this->optionsResolver);
        unset($pdfOptions['scale']);

        self::assertFalse($pdfOptions->offsetExists('scale'));
    }

    public function testOffsetUnsetThrowsExceptionIfAlreadyResolved(): void
    {
        $pdfOptions = new PdfOptions(['scale' => 0.5], $this->optionsResolver);
        $pdfOptions->resolve();

        $this->expectException(PdfOptionsException::class);
        $this->expectExceptionMessage('PDF options are already resolved and cannot be changed');

        $pdfOptions->offsetUnset('scale');
    }
}
