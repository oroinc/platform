<?php

namespace Oro\Bundle\EntityPaginationBundle\Navigation;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager;
use Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class EntityPaginationNavigation
{
    const FIRST    = 'first';
    const PREVIOUS = 'previous';
    const NEXT     = 'next';
    const LAST     = 'last';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var EntityPaginationStorage */
    protected $storage;

    /** @var EntityPaginationManager */
    protected $paginationManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param SecurityFacade $securityFacade
     * @param EntityPaginationStorage $storage
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade,
        EntityPaginationStorage $storage
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->securityFacade = $securityFacade;
        $this->storage = $storage;
    }

    /**
     * @param object $entity
     * @param string $scope
     * @return int|null
     */
    public function getTotalCount($entity, $scope = EntityPaginationManager::VIEW_SCOPE)
    {
        if (!$this->storage->isEnvironmentValid()) {
            return null;
        }

        $total = null;
        if ($this->storage->isEntityInStorage($entity, $scope)) {
            $total = count($this->storage->getEntityIds($entity, $scope));
        }

        return $total;
    }

    /**
     * @param object $entity
     * @param string $scope
     * @return mixed|null
     */
    public function getCurrentNumber($entity, $scope = EntityPaginationManager::VIEW_SCOPE)
    {
        if (!$this->storage->isEnvironmentValid()) {
            return null;
        }

        $currentNumber = null;
        if ($this->storage->isEntityInStorage($entity, $scope)) {
            $currentNumber = $this->storage->getCurrentPosition($entity, $scope) + 1;
        }

        return $currentNumber;
    }

    /**
     * @param $entity
     * @param string $scope
     * @return NavigationResult
     */
    public function getPreviousIdentifier($entity, $scope = EntityPaginationManager::VIEW_SCOPE)
    {
        return $this->getResult($entity, self::PREVIOUS, $scope);
    }

    /**
     * @param object $entity
     * @param string $scope
     * @return NavigationResult
     */
    public function getNextIdentifier($entity, $scope = EntityPaginationManager::VIEW_SCOPE)
    {
        return $this->getResult($entity, self::NEXT, $scope);
    }

    /**
     * @param object $entity
     * @param string $scope
     * @return NavigationResult
     */
    public function getFirstIdentifier($entity, $scope = EntityPaginationManager::VIEW_SCOPE)
    {
        return $this->getResult($entity, self::FIRST, $scope);
    }

    /**
     * @param object $entity
     * @param string $scope
     * @return NavigationResult
     */
    public function getLastIdentifier($entity, $scope = EntityPaginationManager::VIEW_SCOPE)
    {
        return $this->getResult($entity, self::LAST, $scope);
    }

    /**
     * @param object $entity
     * @param string $resultType
     * @param string $scope
     * @return NavigationResult
     */
    protected function getResult($entity, $resultType, $scope = EntityPaginationManager::VIEW_SCOPE)
    {
        $result = new NavigationResult();
        if ($this->storage->isEnvironmentValid() && $this->storage->isEntityInStorage($entity, $scope)) {
            $entityName = ClassUtils::getClass($entity);

            if ($this->isIdentifierMatched($entity, $resultType, $scope)) {
                do {
                    $identifier = $this->getProcessedIdentifier($entity, $resultType, $scope);
                    if (!$identifier) {
                        break;
                    }
                    $navigationEntity = $this->doctrineHelper->getEntity($entityName, $identifier);
                    $permission = EntityPaginationManager::getPermission($scope);
                    if (!$navigationEntity) {
                        $this->storage->unsetIdentifier($identifier, $entity, $scope);
                        $result->setAvailable(false);
                    } elseif (!$this->securityFacade->isGranted($permission, $navigationEntity)) {
                        $this->storage->unsetIdentifier($identifier, $entity, $scope);
                        $result->setAccessible(false);
                    }
                } while (!$navigationEntity || !$this->securityFacade->isGranted($permission, $navigationEntity));

                $result->setId($identifier);
            }
        }

        return $result;
    }

    /**
     * @param object $entity
     * @param $resultType
     * @param string $scope
     * @return bool
     */
    protected function isIdentifierMatched($entity, $resultType, $scope = EntityPaginationManager::VIEW_SCOPE)
    {
        $entityIds = $this->storage->getEntityIds($entity, $scope);
        $currentId = $this->doctrineHelper->getSingleEntityIdentifier($entity);

        $matched = false;
        switch ($resultType) {
            case self::FIRST:
            case self::PREVIOUS:
                $matched = $currentId != reset($entityIds);
                break;
            case self::LAST:
            case self::NEXT:
                $matched = $currentId != end($entityIds);
                break;
        }

        return $matched;
    }

    /**
     * @param object $entity
     * @param string $resultType
     * @param string $scope
     * @return int|null
     */
    protected function getProcessedIdentifier($entity, $resultType, $scope = EntityPaginationManager::VIEW_SCOPE)
    {
        $entityIds = $this->storage->getEntityIds($entity, $scope);

        $entityId = null;
        switch ($resultType) {
            case self::LAST:
                $entityId = end($entityIds);
                break;
            case self::FIRST:
                $entityId = reset($entityIds);
                break;
            case self::PREVIOUS:
                $currentPosition = $this->storage->getCurrentPosition($entity, $scope);
                if (isset($entityIds[$currentPosition - 1])) {
                    $entityId = $entityIds[$currentPosition - 1];
                }
                break;
            case self::NEXT:
                $currentPosition = $this->storage->getCurrentPosition($entity, $scope);
                if (isset($entityIds[$currentPosition + 1])) {
                    $entityId = $entityIds[$currentPosition + 1];
                }
                break;
        }

        return $entityId;
    }
}
