<?php

namespace Oro\Bundle\AssetBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a twig functions that helps set the external asset resources.
 */
class ExternalResourceExtension extends AbstractExtension
{
    public function __construct(private readonly array $externalResources)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('oro_external_link', [$this, 'getExternalResourceLink'], ['is_safe' => ['html']]),
        ];
    }

    public function getExternalResourceLink(string $resourceAlias): string
    {
        if (!isset($this->externalResources[$resourceAlias]['link'])) {
            throw new \LogicException(
                sprintf('External resource link is not configured for alias: %s', $resourceAlias)
            );
        }

        return $this->externalResources[$resourceAlias]['link'];
    }
}
