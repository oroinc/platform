<?php

namespace Oro\Bundle\EntityPaginationBundle\Storage;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class EntityPaginationStorage
{
    const STORAGE_NAME     = 'entity_pagination_storage';
    const ENTITY_NAME      = 'entity_name';
    const GRID_NAME        = 'grid_name';
    const CURRENT_IDS      = 'current_ids';
    const PAGINATION_STATE = 'pagination_state';
    const GRID_STATE       = 'state';
    const PREVIOUS_ID      = 'previous_id';
    const NEXT_ID          = 'next_id';
    const TOTAL            = 'total';

    const WITHOUT_LINK     = false;

    const FIRST_PAGE       = 1;
    const PREVIOUS         = 0;
    const NEXT             = 1;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Manager
     */
    protected $datagridManager;

    /**
     * @param Manager $gridManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(Manager $gridManager, DoctrineHelper $doctrineHelper)
    {
        $this->datagridManager = $gridManager;
        $this->doctrineHelper  = $doctrineHelper;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * @param string $entityName
     * @param string $gridName
     * @param array $paginationState
     */
    public function addData($entityName, $gridName, array $paginationState)
    {
        if ($this->request) {
            $storage = $this->getStorage();
            $storage[$entityName]  = [
                self::GRID_NAME => $gridName,
                self::PAGINATION_STATE => $paginationState
            ];
            $paginationState[self::PREVIOUS_ID] = null;
            $paginationState[self::NEXT_ID]     = null;

            $this->request->getSession()->set(self::STORAGE_NAME, $storage);
        }
    }

    /**
     * @param object $entity
     * @return int|null
     */
    public function getTotalCount($entity)
    {
        if (!$this->prepareCurrentState($entity)) {
            return null;
        }

        $total = null;
        $entityName = $this->getName($entity);
        if ($this->isEntityInStorage($entity)) {
             $total = $this->getStorage()[$entityName][self::PAGINATION_STATE][self::TOTAL];
        }

        return $total;
    }

    /**
     * @param object $entity
     * @return mixed|null
     */
    public function getCurrentNumber($entity)
    {
        if (!$this->prepareCurrentState($entity)) {
            return null;
        }

        $currentPosition = null;
        $entityData = $this->getEntityStorageData($entity);
        if (!empty($entityData)) {
            $gridState = $entityData[self::PAGINATION_STATE][self::GRID_STATE];
            $pager = $gridState[PagerInterface::PAGER_ROOT_PARAM];
            $page = $pager[PagerInterface::PAGE_PARAM];

            $perPage = $pager[PagerInterface::PER_PAGE_PARAM];
            $positionInStorage = array_search(
                $this->getIdentifierValue($entity),
                $entityData[self::PAGINATION_STATE][self::CURRENT_IDS]
            );

            $currentPosition = (($page - 1) * $perPage + $positionInStorage) + 1;
        }

        return $currentPosition;
    }

    /**
     * @param object $entity
     * @return mixed|null
     */
    public function getPreviousIdentifier($entity)
    {
        if (!$this->prepareCurrentState($entity)) {
            return null;
        }

        $previous = null;
        $entityData = $this->getEntityStorageData($entity);
        if (!empty($entityData)) {
            $paginationState = $entityData[self::PAGINATION_STATE];

            if ($paginationState[self::PREVIOUS_ID] !== null) {
                $previous = $paginationState[self::PREVIOUS_ID];
            } else {
                $currentPositionInStorage = array_search(
                    $this->getIdentifierValue($entity),
                    $paginationState[self::CURRENT_IDS]
                );
                $previous = $paginationState[self::CURRENT_IDS][--$currentPositionInStorage];
            }
        }

        return $previous;
    }

    /**
     * @param object $entity
     * @return mixed|null
     */
    public function getNextIdentifier($entity)
    {
        if (!$this->prepareCurrentState($entity)) {
            return null;
        }

        $next = null;
        $entityData = $this->getEntityStorageData($entity);
        if (!empty($entityData)) {
            $paginationState = $entityData[self::PAGINATION_STATE];

            if ($paginationState[self::NEXT_ID] !== null) {
                $next = $paginationState[self::NEXT_ID];
            } else {
                $currentPositionInStorage = array_search(
                    $this->getIdentifierValue($entity),
                    $paginationState[self::CURRENT_IDS]
                );
                $next = $paginationState[self::CURRENT_IDS][++$currentPositionInStorage];
            }
        }

        return $next;
    }

    /**
     * @return array
     */
    private function getStorage()
    {
        return $this->request->getSession()->get(self::STORAGE_NAME, []);
    }

    /**
     * @param object $entity
     * @return bool
     */
    private function isEntityInStorage($entity)
    {
        $storage = $this->getStorage();
        $entityName = $this->getName($entity);
        $entityId = $this->getIdentifierValue($entity);

        return !empty($storage)
            && in_array($entityName, array_keys($storage))
            && (
                in_array(
                    $this->getIdentifierValue($entity),
                    $storage[$entityName][self::PAGINATION_STATE][self::CURRENT_IDS]
                )
                || $storage[$entityName][self::PAGINATION_STATE][self::NEXT_ID] == $entityId
                || $storage[$entityName][self::PAGINATION_STATE][self::PREVIOUS_ID] == $entityId
            );
    }

    /**
     * @param object $entity
     * @return array
     */
    private function getEntityStorageData($entity)
    {
        $storage = $this->getStorage();
        $entityData = array();
        if ($this->isEntityInStorage($entity)) {
            $entityData = $storage[$this->getName($entity)];
        }

        return $entityData;
    }

    /**
     * @param object $entity
     * @return string
     */
    private function getName($entity)
    {
        return ClassUtils::getClass($entity);
    }

    /**
     * @param object $entity
     * @param array $data
     */
    private function updateStorageData($entity, array $data)
    {
        $storage = $this->getStorage();
        $storage[$this->getName($entity)]  = $data;
        $this->request->getSession()->set(self::STORAGE_NAME, $storage);
    }

    /**
     * @param object $entity
     * @return mixed|null
     */
    private function getIdentifierValue($entity)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    /**
     * @param array $entityData
     * @param int $direction
     * @return array
     */
    public function rebuildPaginationState($entity, $entityData, $direction)
    {
        $paginationState = $entityData[self::PAGINATION_STATE];
        $gridState       = $paginationState[self::GRID_STATE];
        $pageNumber      = $gridState[PagerInterface::PAGER_ROOT_PARAM][PagerInterface::PAGE_PARAM];

        switch($direction)
        {
            case self::PREVIOUS:
                $pageNumber--;
                break;
            case self::NEXT:
                $pageNumber++;
                break;
            default:
                throw new LogicException(sprintf('Not supported direction "%s".', $direction));
                break;
        }

        $gridState[ParameterBag::MINIFIED_PARAMETERS][PagerInterface::MINIFIED_PAGE_PARAM] = $pageNumber;
        $gridState[PagerInterface::PAGER_ROOT_PARAM][PagerInterface::PAGE_PARAM] = $pageNumber;

        $dataGrid = $this->datagridManager->getDataGrid($entityData[self::GRID_NAME], $gridState);

        $records          = $dataGrid->getData()->toArray();
        $entityIdentifier = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entity);
        $paginationState[self::GRID_STATE] = $dataGrid->getParameters()->all();
        $paginationState[self::CURRENT_IDS] = [];
        foreach ($records['data'] as $record) {
            $paginationState[self::CURRENT_IDS][] = $record[$entityIdentifier];
        }

        return $paginationState;
    }

    /**
     * @param object $entity
     * @return bool
     */
    private function prepareCurrentState($entity)
    {
        $entityData = $this->getEntityStorageData($entity);
        if (!empty($entityData)) {
            $paginationState = $entityData[self::PAGINATION_STATE];
            $identifierValue = $this->getIdentifierValue($entity);
            $gridState       = $entityData[self::PAGINATION_STATE][self::GRID_STATE];
            $gridPage        = $gridState[PagerInterface::PAGER_ROOT_PARAM][PagerInterface::PAGE_PARAM];
            $gridPerPage     = $gridState[PagerInterface::PAGER_ROOT_PARAM][PagerInterface::PER_PAGE_PARAM];

            if ($identifierValue == $paginationState[self::NEXT_ID]) {
                $nextPaginationState = $this->rebuildPaginationState($entity, $entityData, self::NEXT);
                $nextPaginationState[self::PREVIOUS_ID] = end($paginationState[self::CURRENT_IDS]);
                $nextPaginationState[self::NEXT_ID]     = null;
                $entityData[self::PAGINATION_STATE]     = $nextPaginationState;
            } elseif ($identifierValue == $paginationState[self::PREVIOUS_ID]) {
                $previousPaginationState = $this->rebuildPaginationState($entity, $entityData, self::PREVIOUS);
                $previousPaginationState[self::NEXT_ID]     = $paginationState[self::CURRENT_IDS][0];
                $previousPaginationState[self::PREVIOUS_ID] = null;
                $entityData[self::PAGINATION_STATE]         = $previousPaginationState;
            } elseif (
                $identifierValue == $paginationState[self::CURRENT_IDS][0]
                && !$paginationState[self::PREVIOUS_ID]
            ) {
                if ($gridPage == self::FIRST_PAGE) {
                    $paginationState[self::PREVIOUS_ID] = self::WITHOUT_LINK;
                } else {
                    $previousPaginationState = $this->rebuildPaginationState($entity, $entityData, self::PREVIOUS);
                    $paginationState[self::PREVIOUS_ID] = end($previousPaginationState[self::CURRENT_IDS]);
                }
                $paginationState[self::NEXT_ID]     = null;
                $entityData[self::PAGINATION_STATE] = $paginationState;
            } elseif (
                $identifierValue == end($paginationState[self::CURRENT_IDS])
                && !$paginationState[self::NEXT_ID]
            ) {
                $lastPage = ceil($paginationState[self::TOTAL]/$gridPerPage);
                if ($gridPage == $lastPage) {
                    $paginationState[self::NEXT_ID] = self::WITHOUT_LINK;
                } else {
                    $nextPaginationState = $this->rebuildPaginationState($entity, $entityData, self::NEXT);
                    $paginationState[self::NEXT_ID] = $nextPaginationState[self::CURRENT_IDS][0];
                }
                $paginationState[self::PREVIOUS_ID] = null;
                $entityData[self::PAGINATION_STATE] = $paginationState;
            }

            $this->updateStorageData($entity, $entityData);
            return true;
        }
        return false;
    }
}
