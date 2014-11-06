<?php

namespace Oro\Bundle\EntityPaginationBundle\Twig;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EntityPaginationBundle\Navigation\EntityPaginationNavigation;
use Oro\Bundle\EntityPaginationBundle\Storage\StorageDataCollector;
use Oro\Bundle\EntityPaginationBundle\Manager\MessageManager;

class EntityPaginationExtension extends \Twig_Extension
{
    const NAME = 'oro_entity_pagination';

    /**
     * @var EntityPaginationNavigation
     */
    protected $paginationNavigation;

    /**
     * @var StorageDataCollector
     */
    protected $dataCollector;

    /**
     * @var MessageManager
     */
    protected $messageManager;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param EntityPaginationNavigation $paginationNavigation
     * @param StorageDataCollector $dataCollector
     * @param MessageManager $messageManager
     */
    public function __construct(
        EntityPaginationNavigation $paginationNavigation,
        StorageDataCollector $dataCollector,
        MessageManager $messageManager
    ) {
        $this->paginationNavigation = $paginationNavigation;
        $this->dataCollector = $dataCollector;
        $this->messageManager = $messageManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('oro_entity_pagination_pager', [$this, 'getPager']),
            new \Twig_SimpleFunction('oro_entity_pagination_collect_data', [$this, 'collectData']),
            new \Twig_SimpleFunction('oro_entity_pagination_show_info_message', [$this, 'showInfoMessage']),
        );
    }

    /**
     * Null - pager data is not accessible
     * Array('total' => <int>, 'current' => <int>) - used to generate string "<current> of <total>"
     *
     * @param object $entity
     * @param string $scope
     * @return null|array
     */
    public function getPager($entity, $scope)
    {
        $totalCount = $this->paginationNavigation->getTotalCount($entity, $scope);
        if (!$totalCount) {
            return null;
        }

        $currentNumber = $this->paginationNavigation->getCurrentNumber($entity, $scope);
        if (!$currentNumber) {
            return null;
        }

        return ['total' => $totalCount, 'current' => $currentNumber];
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function collectData($scope)
    {
        return $this->dataCollector->collect($this->request, $scope);
    }

    /**
     * @param object $entity
     * @param string $scope
     */
    public function showInfoMessage($entity, $scope)
    {
        $message = $this->messageManager->getInfoMessage($entity, $scope);
        if ($message) {
            $this->messageManager->addFlashMessage('info', $message);
        }
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
