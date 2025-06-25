<?php

namespace Oro\Bundle\AssetBundle\Twig;

use Oro\Bundle\AssetBundle\Provider\SubresourceIntegrityProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a twig functions that helps set the integrity attribute for a resources.
 */
class SubresourceIntegrityExtension extends AbstractExtension
{
    public function __construct(private readonly SubresourceIntegrityProvider $integrityProvider)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('oro_integrity', [$this, 'getIntegrityHash'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Example: "/build/default/css/app.css"
     */
    public function getIntegrityHash(string $asset): string
    {
        $normalizedAssetName = $this->getNormalizeAssetName($asset);

        $integrityHash = $this->integrityProvider->getHash($normalizedAssetName);
        if (null === $integrityHash) {
            throw new \LogicException(sprintf('An integrity hash does not exist for the asset: %s', $asset));
        }

        return $integrityHash;
    }

    protected function getNormalizeAssetName(string $assetName): string
    {
        return strtok($assetName, '?');
    }
}
