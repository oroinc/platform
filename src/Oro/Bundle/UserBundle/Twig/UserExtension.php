<?php

namespace Oro\Bundle\UserBundle\Twig;

use Oro\Bundle\UserBundle\Provider\GenderProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to display a translated gender label:
 *   - oro_gender
 */
class UserExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('oro_gender', [$this, 'getGenderLabel'])
        ];
    }

    public function getGenderLabel(?string $name): ?string
    {
        if (!$name) {
            return null;
        }

        return $this->getGenderProvider()->getLabelByName($name);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            GenderProvider::class
        ];
    }

    private function getGenderProvider(): GenderProvider
    {
        return $this->container->get(GenderProvider::class);
    }
}
