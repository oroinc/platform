<?php

namespace Oro\Bundle\DataAuditBundle\Twig;

use Oro\Bundle\DataAuditBundle\Provider\AuditTypeInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides the "oro_dataaudit_field_label" Twig function, which names a changed field the way the audited
 * domain shows it, and returns null when the domain has no name of its own for the field.
 */
class AuditFieldLabelExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('oro_dataaudit_field_label', [$this, 'getFieldLabel']),
        ];
    }

    public function getFieldLabel(?string $objectClass, string $field): ?string
    {
        if (null === $objectClass) {
            return null;
        }

        return $this->container->get(AuditTypeInterface::class)->getFieldLabel($objectClass, $field);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            AuditTypeInterface::class,
        ];
    }
}
