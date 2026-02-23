<?php

namespace Oro\Bundle\AssetBundle\Twig;

use Oro\Bundle\AssetBundle\Provider\SubresourceIntegrityProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a twig functions that helps set the integrity attribute for a resources.
 */
class SubresourceIntegrityExtension extends AbstractExtension
{
    public function __construct(
        protected readonly SubresourceIntegrityProvider $integrityProvider,
        protected readonly FeatureChecker $featureChecker
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('oro_integrity', [$this, 'getIntegrityAttribute'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Example: "/build/default/css/app.css"
     */
    public function getIntegrityAttribute(string $asset): string
    {
        if (!$this->featureChecker->isFeatureEnabled('asset_subresource_integrity_enabled')) {
            return '';
        }

        $integrityHash = $this->integrityProvider->getHash($this->getNormalizeAssetName($asset));
        if (null === $integrityHash) {
            return '';
        }

        return \sprintf('integrity="%s" crossorigin="anonymous"', $integrityHash);
    }

    protected function getNormalizeAssetName(string $assetName): string
    {
        return strtok($assetName, '?');
    }
}
