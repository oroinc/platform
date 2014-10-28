<?php

namespace Oro\Bundle\EntityPaginationBundle\Storage;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class EntityPaginationStorage
{
    const VIEW_SCOPE   = 'view';
    const EDIT_SCOPE   = 'edit';
    const STORAGE_NAME = 'entity_pagination_storage';
    const ENTITY_IDS   = 'entity_ids';
    const HASH         = 'hash';

    const FIRST    = 'first';
    const PREVIOUS = 'previous';
    const NEXT     = 'next';
    const LAST     = 'last';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        SecurityFacade $securityFacade
    ) {
        $this->doctrineHelper  = $doctrineHelper;
        $this->configManager = $configManager;
        $this->securityFasade = $securityFacade;
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
    public function setData($entityName, $hash, array $entityIds, $scope = self::VIEW_SCOPE)
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $storage = $this->getStorage();
        if (null === $storage) {
            return false;
        }

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
    public function hasData($entityName, $hash, $scope = self::VIEW_SCOPE)
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $storage = $this->getStorage();
        if (null === $storage) {
            return false;
        }

        return isset($storage[$entityName][$scope][self::HASH]) && $storage[$entityName][$scope][self::HASH] == $hash;
    }

    /**
     * @param object $entity
     * @param string $scope
     * @return int|null
     */
    public function getTotalCount($entity, $scope = self::VIEW_SCOPE)
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $total = null;
        if ($this->isEntityInStorage($entity, $scope)) {
            $total = count($this->getEntityIds($entity, $scope));
        }

        return $total;
    }

    /**
     * @param object $entity
     * @param string $scope
     * @return mixed|null
     */
    public function getCurrentNumber($entity, $scope = self::VIEW_SCOPE)
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $currentNumber = null;
        if ($this->isEntityInStorage($entity, $scope)) {
            $currentNumber = $this->getCurrentPosition($entity, $scope) + 1;
        }

        return $currentNumber;
    }

    /**
     * @param $entity
     * @param string $scope
     * @return EntityPaginationStorageResult
     */
    public function getPreviousIdentifier($entity, $scope = self::VIEW_SCOPE)
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $identifier = null;
        $result = new EntityPaginationStorageResult();
        if ($this->isEntityInStorage($entity, $scope)) {
            $entityIds = $this->getEntityIds($entity, $scope);
            $currentId = $this->getIdentifierValue($entity);
            $entityName = $this->getName($entity);

            if ($currentId != reset($entityIds)) {
                do {
                    $currentPosition = $this->getCurrentPosition($entity, $scope);
                    if (!isset($entityIds[$currentPosition - 1])) {
                        break;
                    }
                    $identifier = $entityIds[$currentPosition - 1];
                    $navigationEntity = $this->doctrineHelper->getEntity($entityName, $identifier);
                    if (!$navigationEntity) {
                        $this->unsetIdentifier($identifier, $entity, $scope);
                        $result->setAvailable(false);
                    } elseif (
                    !$this->securityFasade->isGranted(self::getAttribute($scope), $navigationEntity)
                    ) {
                        $result->setAccessible(false);
                    }
                } while (
                    !$navigationEntity
                    || !$this->securityFasade->isGranted(self::getAttribute($scope), $navigationEntity)
                );

                $result->setId($identifier);
            }
        }

        return $result;
    }

    /**
     * @param object $entity
     * @param string $scope
     * @return EntityPaginationStorageResult
     */
    public function getNextIdentifier($entity, $scope = self::VIEW_SCOPE)
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $identifier = null;
        $result = new EntityPaginationStorageResult();
        if ($this->isEntityInStorage($entity, $scope)) {
            $entityIds = $this->getEntityIds($entity, $scope);
            $currentId = $this->getIdentifierValue($entity);
            $entityName = $this->getName($entity);

            if ($currentId != end($entityIds)) {
                do {
                    $currentPosition = $this->getCurrentPosition($entity, $scope);
                    if (!isset($entityIds[$currentPosition + 1])) {
                        break;
                    }
                    $identifier = $entityIds[$currentPosition + 1];
                    $navigationEntity = $this->doctrineHelper->getEntity($entityName, $identifier);
                    if (!$navigationEntity) {
                        $this->unsetIdentifier($identifier, $entity, $scope);
                        $result->setAvailable(false);
                    } elseif (
                    !$this->securityFasade->isGranted(self::getAttribute($scope), $navigationEntity)
                    ) {
                        $result->setAccessible(false);
                    }
                } while (
                    !$navigationEntity
                    || !$this->securityFasade->isGranted(self::getAttribute($scope), $navigationEntity)
                );

                $result->setId($identifier);
            }
        }

        return $result;
    }

    /**
     * @param object $entity
     * @param string $scope
     * @return EntityPaginationStorageResult
     */
    public function getFirstIdentifier($entity, $scope = self::VIEW_SCOPE)
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $identifier = null;
        $result = new EntityPaginationStorageResult();
        if ($this->isEntityInStorage($entity, $scope)) {
            $entityIds = $this->getEntityIds($entity, $scope);
            $currentId = $this->getIdentifierValue($entity);
            $entityName = $this->getName($entity);

            if ($currentId != reset($entityIds)) {
                do {
                    $identifier = reset($entityIds);
                    $navigationEntity = $this->doctrineHelper->getEntity($entityName, $identifier);
                    if (!$navigationEntity) {
                        $this->unsetIdentifier($identifier, $entity, $scope);
                        $result->setAvailable(false);
                    } elseif (
                    !$this->securityFasade->isGranted(self::getAttribute($scope), $navigationEntity)
                    ) {
                        $result->setAccessible(false);
                    }
                } while (
                    !$navigationEntity
                    || !$this->securityFasade->isGranted(self::getAttribute($scope), $navigationEntity)
                );

                $result->setId($identifier);
            }
        }

        return $result;
    }

    /**
     * @param object $entity
     * @param string $scope
     * @return EntityPaginationStorageResult
     */
    public function getLastIdentifier($entity, $scope = self::VIEW_SCOPE)
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $identifier = null;
        $result = new EntityPaginationStorageResult();
        if ($this->isEntityInStorage($entity, $scope)) {
            $entityIds = $this->getEntityIds($entity, $scope);
            $currentId = $this->getIdentifierValue($entity);
            $entityName = $this->getName($entity);

            if ($currentId != end($entityIds)) {
                do {
                    $identifier = end($entityIds);
                    $navigationEntity = $this->doctrineHelper->getEntity($entityName, $identifier);
                    if (!$navigationEntity) {
                        $this->unsetIdentifier($identifier, $entity, $scope);
                        $result->setAvailable(false);
                    } elseif (
                    !$this->securityFasade->isGranted(self::getAttribute($scope), $navigationEntity)
                    ) {
                        $result->setAccessible(false);
                    }
                } while (
                    !$navigationEntity
                    || !$this->securityFasade->isGranted(self::getAttribute($scope), $navigationEntity)
                );

                $result->setId($identifier);
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->configManager->get('oro_entity_pagination.enabled');
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return (int)$this->configManager->get('oro_entity_pagination.limit');
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
    protected function isEntityInStorage($entity, $scope = self::VIEW_SCOPE)
    {
        $storage = $this->getStorage();
        if (null === $storage) {
            return false;
        }

        $entityName = $this->getName($entity);
        $identifierValue = $this->getIdentifierValue($entity);

        return !empty($storage[$entityName][$scope][self::ENTITY_IDS])
            && in_array($identifierValue, $storage[$entityName][$scope][self::ENTITY_IDS]);
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
     * @return mixed|null
     */
    protected function getIdentifierValue($entity)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    /**
     * @param object $entity
     * @param string $scope
     * @return array
     */
    protected function getEntityIds($entity, $scope = self::VIEW_SCOPE)
    {
        $entityName = $this->getName($entity);
        $storage = $this->getStorage();
        $entityIds = [];
        if ($storage && !empty($storage[$entityName][$scope][self::ENTITY_IDS])) {
            $entityIds = $storage[$entityName][$scope][self::ENTITY_IDS];
        }

        return $entityIds;
    }

    /**
     * @param object $entity
     * @param string $scope
     * @return int
     */
    protected function getCurrentPosition($entity, $scope = self::VIEW_SCOPE)
    {
        return array_search(
            $this->getIdentifierValue($entity, $scope),
            $this->getEntityIds($entity, $scope)
        );
    }

    /**
     * @param string $scope
     * @return string
     */
    public static function getAttribute($scope)
    {
        switch ($scope) {
            case self::VIEW_SCOPE:
                $attribute = 'VIEW';
                break;
            case self::EDIT_SCOPE:
                $attribute = 'EDIT';
                break;
            default:
                throw new \LogicException(sprintf('Scope "%s" is not available.', $scope));
        }

        return $attribute;
    }

    /**
     * @param object $entity
     * @param string $scope
     * @param array $entityIds
     */
    protected function updateStorageData($entity, $scope, array $entityIds)
    {
        $storage = $this->getStorage();
        $storage[$this->getName($entity)][$scope][self::ENTITY_IDS] = $entityIds;
        $this->setStorage($storage);
    }

    protected function unsetIdentifier($identifier, $entity, $scope)
    {
        $entityIds = $this->getEntityIds($entity, $scope);
        $entityKey = array_search($identifier, $entityIds);
        unset($entityIds[$entityKey]);
        $entityIds = array_values($entityIds);
        $this->updateStorageData($entity, $scope, $entityIds);
    }
}
