<?php

namespace Oro\Bundle\EntityPaginationBundle\Storage;

use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

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
    const PREVIOUS         = 'previous';
    const NEXT             = 'next';

    const WITHOUT_LINK     = false;

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
     * Add data to storage
     *
     * The PaginationState must have the following structure:
     * <code>
     * array(
     *     'state' => [
     *          '_pager' => [
     *              '_page' => 3,
     *              '_per_page' => 10
     *          ],
     *          '_sort_by' => [
     *              'name' => 'ASC'
     *          ],
     *          '_filter' => []
     *      ],
     *      'current_ids' => [4, 28, 37, 29, 7, 20, 15],
     *      'total' => 27
     * )
     * </code>
     *
     * @param string $entityName
     * @param string $gridName
     * @param array $paginationState
     *
     * @return boolean
     */
    public function addData($entityName, $gridName, array $paginationState)
    {
        if ($this->request) {
            $storage                            = $this->getStorage();
            $paginationState[self::PREVIOUS_ID] = null;
            $paginationState[self::NEXT_ID]     = null;
            $storage[$entityName]               = [
                self::GRID_NAME => $gridName,
                self::PAGINATION_STATE => $paginationState
            ];

            $this->setStorage($storage);
            return true;
        }
        return false;
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
            $identifierValue = $this->getIdentifierValue($entity);

            if (
                $paginationState[self::PREVIOUS_ID] !== null
                && $identifierValue == reset($paginationState[self::CURRENT_IDS])
            ) {
                $previous = $paginationState[self::PREVIOUS_ID];
            } else {
                $currentPositionInStorage = array_search($identifierValue, $paginationState[self::CURRENT_IDS]);
                $previous = $paginationState[self::CURRENT_IDS][--$currentPositionInStorage];
            }
        }

        return $previous ?: null;
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
            $identifierValue = $this->getIdentifierValue($entity);

            if (
                $paginationState[self::NEXT_ID] !== null
                && $identifierValue == end($paginationState[self::CURRENT_IDS])
            ) {
                $next = $paginationState[self::NEXT_ID];
            } else {
                $currentPositionInStorage = array_search($identifierValue, $paginationState[self::CURRENT_IDS]);
                $next = $paginationState[self::CURRENT_IDS][++$currentPositionInStorage];
            }
        }

        return $next ?: null;
    }

    /**
     * @return array
     */
    protected function getStorage()
    {
        if ($this->request) {
            return $this->request->getSession()->get(self::STORAGE_NAME, []);
        }
        return [];
    }

    /**
     * @param array $storage
     */
    protected function setStorage(array $storage)
    {
        $this->request->getSession()->set(self::STORAGE_NAME, $storage);
    }

    /**
     * @param object $entity
     * @return bool
     */
    protected function isEntityInStorage($entity)
    {
        $storage = $this->getStorage();
        $entityName = $this->getName($entity);
        $identifierValue = $this->getIdentifierValue($entity);

        return !empty($storage[$entityName])
            && (
                in_array($identifierValue, $storage[$entityName][self::PAGINATION_STATE][self::CURRENT_IDS])
                || $storage[$entityName][self::PAGINATION_STATE][self::NEXT_ID] == $identifierValue
                || $storage[$entityName][self::PAGINATION_STATE][self::PREVIOUS_ID] == $identifierValue
            );
    }

    /**
     * @param object $entity
     * @return array
     */
    protected function getEntityStorageData($entity)
    {
        $storage = $this->getStorage();
        $entityData = [];
        if ($this->isEntityInStorage($entity)) {
            $entityData = $storage[$this->getName($entity)];
        }

        return $entityData;
    }

    /**
     * @param object $entity
     * @return string
     */
    protected function getName($entity)
    {
        return ClassUtils::getClass($entity);
    }

    /**
     * @param object $entity
     * @param array $data
     */
    protected function updateStorageData($entity, array $data)
    {
        $storage = $this->getStorage();
        $storage[$this->getName($entity)]  = $data;
        $this->setStorage($storage);
    }

    /**
     * @param object $entity
     * @return mixed|null
     */
    protected function getIdentifierValue($entity)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    /**
     * @param object $entity
     * @param array $entityData
     * @param int $direction
     * @return array
     * @throws \LogicException
     */
    protected function rebuildPaginationState($entity, $entityData, $direction)
    {
        $paginationState = $entityData[self::PAGINATION_STATE];
        $gridState       = $paginationState[self::GRID_STATE];
        $pageNumber      = $gridState[PagerInterface::PAGER_ROOT_PARAM][PagerInterface::PAGE_PARAM];

        switch($direction) {
            case self::PREVIOUS:
                $pageNumber--;
                break;
            case self::NEXT:
                $pageNumber++;
                break;
            default:
                throw new \LogicException(sprintf('Not supported direction "%s".', $direction));
        }

        $gridState[PagerInterface::PAGER_ROOT_PARAM][PagerInterface::PAGE_PARAM] = $pageNumber;

        /*
         * Build grid with new grid state (next page or previous page state).
         * Get data for this state and return pagination state filled with new current ids.
         */
        $dataGrid         = $this->datagridManager->getDataGrid($entityData[self::GRID_NAME], $gridState);
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
    protected function prepareCurrentState($entity)
    {
        $entityData = $this->getEntityStorageData($entity);
        if (!empty($entityData)) {
            $paginationState = $entityData[self::PAGINATION_STATE];
            $identifierValue = $this->getIdentifierValue($entity);
            $gridState       = $entityData[self::PAGINATION_STATE][self::GRID_STATE];
            $gridPage        = $gridState[PagerInterface::PAGER_ROOT_PARAM][PagerInterface::PAGE_PARAM];
            $gridPerPage     = $gridState[PagerInterface::PAGER_ROOT_PARAM][PagerInterface::PER_PAGE_PARAM];
            $needUpdate      = false;

            if ($identifierValue == $paginationState[self::NEXT_ID]) {
                /*
                 * If entity identifier value is equal $paginationState['next_id'] value, it means that entity
                 * is not in $paginationState['current_ids'] kit, and should to generate pagination state for next page.
                 * The last element of current ids kit will be $nextPaginationState['previous_id'],
                 * and storage data for current entity name updated by the next page pagination state.
                */
                $nextPaginationState = $this->rebuildPaginationState($entity, $entityData, self::NEXT);
                $nextPaginationState[self::PREVIOUS_ID] = end($paginationState[self::CURRENT_IDS]);
                $nextPaginationState[self::NEXT_ID]     = null;
                $entityData[self::PAGINATION_STATE]     = $nextPaginationState;
                $needUpdate                             = true;
            } elseif ($identifierValue == $paginationState[self::PREVIOUS_ID]) {
                /*
                 * If entity identifier value is equal $paginationState['previous_id'] value, it means that entity
                 * is not in $paginationState['current_ids'] kit, and should to generate pagination state for
                 * previous page. The first element of current ids kit will be $previousPaginationState['next_id'],
                 * and storage data for current entity name updated by the previous page pagination state.
                */
                $previousPaginationState = $this->rebuildPaginationState($entity, $entityData, self::PREVIOUS);
                $previousPaginationState[self::NEXT_ID]     = reset($paginationState[self::CURRENT_IDS]);
                $previousPaginationState[self::PREVIOUS_ID] = null;
                $entityData[self::PAGINATION_STATE]         = $previousPaginationState;
                $needUpdate                                 = true;
            } else {
                /*
                 * If entity identifier value is at the beginning of the list current $paginationState['current_ids'],
                 * and current $paginationState['previous_id'] is not defined yet should to define
                 * $paginationState['previous_id'].  In this case need to set $paginationState['previous_id'].
                 * Previous id will be FALSE when current entity is a first element on the first page,
                 * otherwise it will be the last element of generated $previousPaginationState['current_ids'].
                 * Storage data for current entity name updated by $paginationState['previous_id'] value.
                 */
                if (
                    $identifierValue == reset($paginationState[self::CURRENT_IDS])
                    && null === $paginationState[self::PREVIOUS_ID]
                ) {
                    if ($gridPage == 1) {
                        $paginationState[self::PREVIOUS_ID] = self::WITHOUT_LINK;
                    } else {
                        $previousPaginationState = $this->rebuildPaginationState($entity, $entityData, self::PREVIOUS);
                        $paginationState[self::PREVIOUS_ID] = end($previousPaginationState[self::CURRENT_IDS]);
                    }
                    $entityData[self::PAGINATION_STATE] = $paginationState;
                    $needUpdate                         = true;
                }

                /*
                 * If entity identifier value is at the end of the list current $paginationState['current_ids'],
                 * and current $paginationState['next_id'] is not defined yet should to define
                 * $paginationState['next_id']. In this case need to set $paginationState['next_id'].
                 * Next id will be FALSE when current entity is a last element on the last page,
                 * otherwise it will be the first element of generated $nextPaginationState['current_ids'].
                 * Storage data for current entity name updated by $paginationState['next_id'] value.
                 */
                if (
                    $identifierValue == end($paginationState[self::CURRENT_IDS])
                    && null === $paginationState[self::NEXT_ID]
                ) {
                    $lastPage = ceil($paginationState[self::TOTAL] / $gridPerPage);
                    if ($gridPage == $lastPage) {
                        $paginationState[self::NEXT_ID] = self::WITHOUT_LINK;
                    } else {
                        $nextPaginationState = $this->rebuildPaginationState($entity, $entityData, self::NEXT);
                        $paginationState[self::NEXT_ID] = reset($nextPaginationState[self::CURRENT_IDS]);
                    }
                    $entityData[self::PAGINATION_STATE] = $paginationState;
                    $needUpdate                         = true;
                }
            }

            if ($needUpdate) {
                $this->updateStorageData($entity, $entityData);
            }

            return true;
        }

        return false;
    }
}
