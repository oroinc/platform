<?php

namespace Oro\Bundle\EntityPaginationBundle\Storage;

use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class EntityPaginationStorage
{
    const STORAGE_NAME = 'entity_pagination_storage';
    const ENTITY_IDS   = 'entity_ids';
    const HASH         = 'hash';
    const INFO_MESSAGE = 'info_message';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EntityPaginationManager $paginationManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, EntityPaginationManager $paginationManager)
    {
        $this->doctrineHelper  = $doctrineHelper;
        $this->paginationManager = $paginationManager;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * Add data to storage that has following structure:
     * [
     *      '<entityName>' => [
     *          'hash' => '<string>',
     *          'entity_ids => [<id1>, <id2>, ...]
     *      ],
     *      ...
     * ]
     *
     * @param string $entityName
     * @param string $hash
     * @param array $entityIds
     * @param string $scope
     * @return bool
     */
    public function setData($entityName, $hash, array $entityIds, $scope)
    {
        if (!$this->isEnvironmentValid()) {
            return false;
        }

        $storage = $this->getStorage();
        $storage[$entityName][$scope] = [self::HASH => $hash, self::ENTITY_IDS => $entityIds];
        $this->setStorage($storage);

        return true;
    }

    /**
     * @param string $entityName
     * @param string $hash
     * @param string $scope
     * @return bool
     */
    public function hasData($entityName, $hash, $scope)
    {
        if (!$this->isEnvironmentValid()) {
            return false;
        }

        $storage = $this->getStorage();

        return isset($storage[$entityName][$scope][self::HASH]) && $storage[$entityName][$scope][self::HASH] == $hash;
    }

    /**
     * @param string $entityName
     * @param string $scope
     * @return bool|null
     */
    public function isInfoMessageShown($entityName, $scope)
    {
        if (!$this->isEnvironmentValid()) {
            return null;
        }

        $storage = $this->getStorage();

        return !empty($storage[$entityName][$scope][self::INFO_MESSAGE]);
    }

    /**
     * @param string $entityName
     * @param string $scope
     * @param bool $shown
     * @return bool
     */
    public function setInfoMessageShown($entityName, $scope, $shown = true)
    {
        if (!$this->isEnvironmentValid()) {
            return false;
        }

        $storage = $this->getStorage();
        $storage[$entityName][$scope][self::INFO_MESSAGE] = $shown;
        $this->setStorage($storage);

        return true;
    }

    /**
     * @param string $entityName
     * @param string $scope
     * @return bool
     */
    public function clearData($entityName, $scope = null)
    {
        if (!$this->isEnvironmentValid()) {
            return false;
        }

        $storage = $this->getStorage();

        $clear = false;
        if ($scope !== null && isset($storage[$entityName][$scope])) {
            // clear only specified scope
            unset($storage[$entityName][$scope]);
            $this->setStorage($storage);
            $clear = true;
        } elseif ($scope === null && isset($storage[$entityName])) {
            // clear all scopes
            unset($storage[$entityName]);
            $this->setStorage($storage);
            $clear = true;
        }

        return $clear;
    }

    /**
     * @return array|null
     */
    protected function getStorage()
    {
        if ($this->request) {
            return $this->request->getSession()->get(self::STORAGE_NAME, []);
        } else {
            return null;
        }
    }

    /**
     * @param array $storage
     */
    protected function setStorage(array $storage)
    {
        if ($this->request) {
            $this->request->getSession()->set(self::STORAGE_NAME, $storage);
        }
    }

    /**
     * @param object $entity
     * @param string $scope
     * @return bool
     */
    public function isEntityInStorage($entity, $scope)
    {
        $entityName = ClassUtils::getClass($entity);
        $storage = $this->getStorage();
        $identifierValue = $this->doctrineHelper->getSingleEntityIdentifier($entity);

        return !empty($storage[$entityName][$scope][self::ENTITY_IDS])
            && in_array($identifierValue, $storage[$entityName][$scope][self::ENTITY_IDS]);
    }

    /**
     * @param object $entity
     * @param string $scope
     * @return array
     */
    public function getEntityIds($entity, $scope)
    {
        $entityName = ClassUtils::getClass($entity);
        $storage = $this->getStorage();
        $entityIds = [];
        if (!empty($storage[$entityName][$scope][self::ENTITY_IDS])) {
            $entityIds = $storage[$entityName][$scope][self::ENTITY_IDS];
        }

        return $entityIds;
    }

    /**
     * @param object $entity
     * @param string $scope
     * @return int
     */
    public function getCurrentPosition($entity, $scope)
    {
        return array_search(
            $this->doctrineHelper->getSingleEntityIdentifier($entity),
            $this->getEntityIds($entity, $scope)
        );
    }

    /**
     * @param int $identifier
     * @param object $entity
     * @param string $scope
     */
    public function unsetIdentifier($identifier, $entity, $scope)
    {
        $entityIds = $this->getEntityIds($entity, $scope);
        $entityKey = array_search($identifier, $entityIds);
        unset($entityIds[$entityKey]);
        $entityIds = array_values($entityIds);

        $entityName = ClassUtils::getClass($entity);
        $storage = $this->getStorage();
        $storage[$entityName][$scope][self::ENTITY_IDS] = $entityIds;
        $this->setStorage($storage);
    }

    /**
     * @return bool
     */
    public function isEnvironmentValid()
    {
        return $this->paginationManager->isEnabled() && $this->getStorage() !== null;
    }
}
