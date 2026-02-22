<?php

namespace Oro\Bundle\EntityPaginationBundle\Twig;

use Oro\Bundle\EntityPaginationBundle\Manager\MessageManager;
use Oro\Bundle\EntityPaginationBundle\Navigation\EntityPaginationNavigation;
use Oro\Bundle\EntityPaginationBundle\Storage\StorageDataCollector;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions for entity pagination:
 *   - oro_entity_pagination_pager
 *   - oro_entity_pagination_collect_data
 *   - oro_entity_pagination_show_info_message
 */
class EntityPaginationExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_entity_pagination_pager', [$this, 'getPager']),
            new TwigFunction('oro_entity_pagination_collect_data', [$this, 'collectData']),
            new TwigFunction('oro_entity_pagination_show_info_message', [$this, 'showInfoMessage']),
        ];
    }

    /**
     * Null - pager data is not accessible
     * Array('total' => <int>, 'current' => <int>) - used to generate string "<current> of <total>"
     *
     * @param object $entity
     * @param string $scope
     *
     * @return null|array
     */
    public function getPager($entity, $scope)
    {
        $paginationNavigation = $this->getPaginationNavigation();
        $totalCount = $paginationNavigation->getTotalCount($entity, $scope);
        if (!$totalCount) {
            return null;
        }

        $currentNumber = $paginationNavigation->getCurrentNumber($entity, $scope);
        if (!$currentNumber) {
            return null;
        }

        return ['total' => $totalCount, 'current' => $currentNumber];
    }

    /**
     * @param string $scope
     *
     * @return bool
     */
    public function collectData($scope)
    {
        return $this->getStorageDataCollector()
            ->collect($this->getRequestStack()->getCurrentRequest(), $scope);
    }

    /**
     * @param object $entity
     * @param string $scope
     */
    public function showInfoMessage($entity, $scope)
    {
        $messageManager = $this->getMessageManager();
        $message = $messageManager->getInfoMessage($entity, $scope);
        if ($message) {
            $messageManager->addFlashMessage('info', $message);
        }
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            EntityPaginationNavigation::class,
            StorageDataCollector::class,
            MessageManager::class,
            RequestStack::class
        ];
    }

    private function getPaginationNavigation(): EntityPaginationNavigation
    {
        return $this->container->get(EntityPaginationNavigation::class);
    }

    private function getStorageDataCollector(): StorageDataCollector
    {
        return $this->container->get(StorageDataCollector::class);
    }

    private function getMessageManager(): MessageManager
    {
        return $this->container->get(MessageManager::class);
    }

    private function getRequestStack(): RequestStack
    {
        return $this->container->get(RequestStack::class);
    }
}
