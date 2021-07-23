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
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return EntityPaginationNavigation
     */
    protected function getPaginationNavigation()
    {
        return $this->container->get('oro_entity_pagination.navigation');
    }

    /**
     * @return StorageDataCollector
     */
    protected function getStorageDataCollector()
    {
        return $this->container->get('oro_entity_pagination.storage.data_collector');
    }

    /**
     * @return MessageManager
     */
    protected function getMessageManager()
    {
        return $this->container->get('oro_entity_pagination.message_manager');
    }

    /**
     * @return RequestStack
     */
    protected function getRequestStack()
    {
        return $this->container->get(RequestStack::class);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_entity_pagination.navigation' => EntityPaginationNavigation::class,
            'oro_entity_pagination.storage.data_collector' => StorageDataCollector::class,
            'oro_entity_pagination.message_manager' => MessageManager::class,
            RequestStack::class,
        ];
    }
}
