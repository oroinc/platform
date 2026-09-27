<?php

namespace Oro\Bundle\DataAuditBundle\Twig;

use Oro\Bundle\DataAuditBundle\Provider\MenuAuditObjectProviderInterface;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides the "oro_dataaudit_menu_item_audit" Twig function, which tells the change history of which audit
 * object the page of a menu item shows, and returns null when the item has no history of its own.
 */
class MenuAuditExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private const string ADDRESSABLE_ID = '/^[a-zA-Z0-9_-]+$/';

    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('oro_dataaudit_menu_item_audit', [$this, 'getMenuItemAudit']),
        ];
    }

    /**
     * @return array{class: string, id: string}|null
     */
    public function getMenuItemAudit(?MenuUpdateInterface $menuUpdate): ?array
    {
        if (null === $menuUpdate) {
            return null;
        }

        $auditObject = $this->container->get(MenuAuditObjectProviderInterface::class)->getAuditObject($menuUpdate);
        if (null === $auditObject || !preg_match(self::ADDRESSABLE_ID, $auditObject->getObjectId())) {
            return null;
        }

        return [
            'class' => $this->container->get(EntityClassNameHelper::class)
                ->getUrlSafeClassName($auditObject->getObjectClass()),
            'id' => $auditObject->getObjectId(),
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            MenuAuditObjectProviderInterface::class,
            EntityClassNameHelper::class,
        ];
    }
}
