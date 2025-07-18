<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer\Twig;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManagerInterface;
use Oro\Bundle\AttachmentBundle\Twig\FileExtension;
use Oro\Bundle\PdfGeneratorBundle\Exception\PdfTemplateAssetException;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer\AssetsCollector\PdfTemplateAssetsCollectorInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Overrides "asset" TWIG function to make use of the PDF template assets collector.
 */
class PdfTemplateAssetsCollectorExtension extends AbstractExtension implements ResetInterface
{
    public function __construct(
        private Packages $packages,
        private ImageResizeManagerInterface $imageResizeManager,
        private PdfTemplateAssetsCollectorInterface $pdfTemplateAssetsCollector
    ) {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset', $this->getAssetUrl(...)),
            new TwigFunction('filtered_image_url', $this->getFilteredImageUrl(...)),
            new TwigFunction('resized_image_url', $this->getResizedImageUrl(...)),
        ];
    }

    /**
     * Returns the name of an asset.
     * Adds the asset to the PDF template assets collector.
     */
    public function getAssetUrl(string $path, ?string $packageName = null): string
    {
        $url = $this->packages->getUrl($path, $packageName);

        return $this->pdfTemplateAssetsCollector->addStaticAsset($url);
    }

    /**
     * @throws PdfTemplateAssetException
     */
    public function getFilteredImageUrl(File $file, string $filterName, string $format = ''): string
    {
        try {
            $binary = $this->imageResizeManager->applyFilter($file, $filterName, $format);
            $name = basename($file->getFilename());

            if ($binary !== null) {
                $this->pdfTemplateAssetsCollector->addRawAsset($binary->getContent(), $name);
            }
        } catch (\Throwable $throwable) {
            throw new PdfTemplateAssetException(
                sprintf(
                    'Failed to add a PDF template asset for: file="%s", filter="%s", format="%s"',
                    $file->getFilename(),
                    $filterName,
                    $format
                ),
                $throwable->getCode(),
                $throwable
            );
        }

        return $name;
    }

    /**
     * @throws PdfTemplateAssetException
     */
    public function getResizedImageUrl(
        File $file,
        int $width = FileExtension::DEFAULT_THUMB_SIZE,
        int $height = FileExtension::DEFAULT_THUMB_SIZE,
        string $format = ''
    ): string {
        try {
            $binary = $this->imageResizeManager->resize($file, $width, $height, $format);
            $name = basename($file->getFilename());

            if ($binary !== null) {
                $this->pdfTemplateAssetsCollector->addRawAsset($binary->getContent(), $name);
            }
        } catch (\Throwable $throwable) {
            throw new PdfTemplateAssetException(
                sprintf(
                    'Failed to add a PDF template asset for: file="%s", width="%s", height="%s", format="%s"',
                    $file->getFilename(),
                    $width,
                    $height,
                    $format
                ),
                $throwable->getCode(),
                $throwable
            );
        }

        return $name;
    }

    /**
     * @return array<string,PdfTemplateAssetInterface>
     */
    public function getAssets(): array
    {
        return $this->pdfTemplateAssetsCollector->getAssets();
    }

    #[\Override]
    public function reset(): void
    {
        $this->pdfTemplateAssetsCollector->reset();
    }
}
