<?php

namespace Oro\Bundle\DataAuditBundle\Twig;

use Oro\Bundle\DataAuditBundle\Provider\ConfigAuditFieldLabelProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides the `oro_dataaudit_config_field_label` Twig function, which resolves a stored configuration
 * key to its breadcrumb + label for display in the Data Audit grid. Returns null for non-configuration
 * audit rows, so the caller can fall back to the default field-name rendering.
 */
class ConfigAuditExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('oro_dataaudit_config_field_label', [$this, 'getConfigFieldLabel']),
        ];
    }

    public function getConfigFieldLabel(?string $objectClass, string $fieldKey): ?string
    {
        return $this->container->get(ConfigAuditFieldLabelProvider::class)->getLabel($objectClass, $fieldKey);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ConfigAuditFieldLabelProvider::class,
        ];
    }
}
