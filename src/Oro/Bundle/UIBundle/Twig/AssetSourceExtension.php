<?php

declare(strict_types=1);

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Bundle\DistributionBundle\Provider\PublicDirectoryProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig function to get an asset source code:
 *   - asset_source
 */
class AssetSourceExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset_source', $this->getAssetSource(...)),
        ];
    }

    public function getAssetSource(string $path): string
    {
        $publicDir = $this->getPublicDirectoryProvider()->getPublicDirectory();
        $fullPath = (string)realpath($publicDir . DIRECTORY_SEPARATOR . $path);
        if (!file_exists($fullPath) || !is_readable($fullPath) || !str_starts_with($fullPath, $publicDir)) {
            return '';
        }

        return file_get_contents($fullPath);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            PublicDirectoryProvider::class
        ];
    }

    private function getPublicDirectoryProvider(): PublicDirectoryProvider
    {
        return $this->container->get(PublicDirectoryProvider::class);
    }
}
