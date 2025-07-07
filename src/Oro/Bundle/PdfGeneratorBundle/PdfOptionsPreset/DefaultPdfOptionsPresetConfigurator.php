<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset;

use Oro\Bundle\PdfGeneratorBundle\Model\Size;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplateInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetFactoryInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Configures PDF options common for all PDF engines and PDF options presets.
 */
class DefaultPdfOptionsPresetConfigurator implements PdfOptionsPresetConfiguratorInterface
{
    public function __construct(private PdfTemplateAssetFactoryInterface $pdfTemplateAssetFactory)
    {
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('content')
            ->required()
            ->allowedTypes(PdfTemplateInterface::class)
            ->info('Sets the page main content template.');

        $resolver
            ->define('header')
            ->allowedTypes(PdfTemplateInterface::class)
            ->info('Sets the page header content template.');

        $resolver
            ->define('footer')
            ->allowedTypes(PdfTemplateInterface::class)
            ->info('Sets the page footer content template.');

        $resolver
            ->define('assets')
            ->allowedTypes(PdfTemplateAssetInterface::class . '[]', 'string[]')
            ->normalize($this->normalizeAssets(...))
            ->default([])
            ->info(
                'Collection of assets where key is an asset name and '
                . 'value is an instance of PdfTemplateAssetInterface instances.'
            );

        $resolver
            ->define('page_width')
            ->allowedTypes(Size::class, 'string', 'int', 'float')
            ->normalize(self::sizeNormalizer(...))
            ->info('Sets the page width.');

        $resolver
            ->define('page_height')
            ->allowedTypes(Size::class, 'string', 'int', 'float')
            ->normalize(self::sizeNormalizer(...))
            ->info('Sets the page height.');

        $resolver
            ->define('margin_top')
            ->allowedTypes(Size::class, 'string', 'int', 'float')
            ->normalize(self::sizeNormalizer(...))
            ->info('Sets the page top margin.');

        $resolver
            ->define('margin_right')
            ->allowedTypes(Size::class, 'string', 'int', 'float')
            ->normalize(self::sizeNormalizer(...))
            ->info('Sets the page right margin.');

        $resolver
            ->define('margin_bottom')
            ->allowedTypes(Size::class, 'string', 'int', 'float')
            ->normalize(self::sizeNormalizer(...))
            ->info('Sets the page bottom margin.');

        $resolver
            ->define('margin_left')
            ->allowedTypes(Size::class, 'string', 'int', 'float')
            ->normalize(self::sizeNormalizer(...))
            ->info('Sets the page left margin.');

        $resolver
            ->define('landscape')
            ->allowedTypes('bool')
            ->default(false)
            ->info('Sets the page orientation to landscape.');

        $resolver
            ->define('scale')
            ->allowedTypes('string', 'int', 'float')
            ->normalize(self::floatNormalizer(...))
            ->default(1.0)
            ->info('Sets the scale of the page rendering (i.e., 1.0 is 100%).');

        $resolver
            ->define('custom_options')
            ->allowedTypes('array')
            ->default([])
            ->info('Allows to pass custom options not defined by any PDF options configurator.');
    }

    #[\Override]
    public function isApplicable(string $pdfEngineName, string $pdfOptionsPreset = PdfOptionsPreset::DEFAULT): bool
    {
        return $pdfOptionsPreset === PdfOptionsPreset::DEFAULT;
    }

    /**
     * @param Options $options
     * @param array<PdfTemplateAssetInterface|string> $assets
     *
     * @return array<PdfTemplateAssetInterface>
     */
    public function normalizeAssets(Options $options, array $assets): array
    {
        foreach ($assets as $i => $asset) {
            if ($asset instanceof PdfTemplateAssetInterface) {
                continue;
            }

            $assets[$i] = $this->pdfTemplateAssetFactory->createFromPath($asset);
        }

        return $assets;
    }

    private static function sizeNormalizer(Options $options, Size|string|int|float $size): Size
    {
        if ($size instanceof Size) {
            return $size;
        }

        return Size::create($size);
    }

    private static function floatNormalizer(Options $options, string|int|float $value): float
    {
        return (float) $value;
    }
}
