<?php

namespace Oro\Bundle\EntityPaginationBundle\Twig;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EntityPaginationBundle\Navigation\EntityPaginationNavigation;
use Oro\Bundle\EntityPaginationBundle\Storage\StorageDataCollector;

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
     * @var Request
     */
    protected $request;

    /**
     * @param EntityPaginationNavigation $paginationNavigation
     * @param StorageDataCollector $dataCollector
     */
    public function __construct(
        EntityPaginationNavigation $paginationNavigation,
        StorageDataCollector $dataCollector
    ) {
        $this->paginationNavigation = $paginationNavigation;
        $this->dataCollector = $dataCollector;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('oro_entity_pagination_pager', [$this, 'getPager']),
            new \Twig_SimpleFunction('oro_entity_pagination_collect_data', [$this, 'collectData']),
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
