<?php

namespace Oro\Bundle\EntityPaginationBundle\Storage;

use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class EntityPaginationStorage
{
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

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper  = $doctrineHelper;
        $this->configManager = $configManager;
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
     * @return bool
     */
    public function setData($entityName, $hash, array $entityIds)
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $storage = $this->getStorage();
        if (null === $storage) {
            return false;
        }

        $storage[$entityName] = [self::HASH => $hash, self::ENTITY_IDS => $entityIds];
        $this->setStorage($storage);

        return true;
    }

    /**
     * @param string $entityName
     * @param string $hash
     * @return bool
     */
    public function hasData($entityName, $hash)
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $storage = $this->getStorage();
        if (null === $storage) {
            return false;
        }

        return isset($storage[$entityName][self::HASH]) && $storage[$entityName][self::HASH] == $hash;
    }

    /**
     * @param object $entity
     * @return int|null
     */
    public function getTotalCount($entity)
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $total = null;
        if ($this->isEntityInStorage($entity)) {
            $total = count($this->getEntityIds($entity));
        }

        return $total;
    }

    /**
     * @param object $entity
     * @return mixed|null
     */
    public function getCurrentNumber($entity)
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $currentNumber = null;
        if ($this->isEntityInStorage($entity)) {
            $currentNumber = $this->getCurrentPosition($entity) + 1;
        }

        return $currentNumber;
    }

    /**
     * @param $entity
     * @return int|null
     */
    public function getPreviousIdentifier($entity)
    {
        return $this->getIdentifier($entity, self::PREVIOUS);
    }

    /**
     * @param object $entity
     * @return mixed|null
     */
    public function getNextIdentifier($entity)
    {
        return $this->getIdentifier($entity, self::NEXT);
    }

    /**
     * @param object $entity
     * @return mixed|null
     */
    public function getFirstIdentifier($entity)
    {
        return $this->getIdentifier($entity, self::FIRST);
    }

    /**
     * @param object $entity
     * @return mixed|null
     */
    public function getLastIdentifier($entity)
    {
        return $this->getIdentifier($entity, self::LAST);
    }

    /**
     * @param object $entity
     * @param string $navigation
     * @return mixed|null
     */
    protected function getIdentifier($entity, $navigation)
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $identifier = null;
        if ($this->isEntityInStorage($entity)) {
            $entityIds = $this->getEntityIds($entity);
            $currentId = $this->getIdentifierValue($entity);
            switch ($navigation) {
                case self::FIRST:
                    if ($currentId != reset($entityIds)) {
                        $identifier = reset($entityIds);
                    }
                    break;
                case self::PREVIOUS:
                    if ($currentId != reset($entityIds)) {
                        $currentPosition = $this->getCurrentPosition($entity);
                        $identifier = $entityIds[--$currentPosition];
                    }
                    break;
                case self::NEXT:
                    if ($currentId != end($entityIds)) {
                        $currentPosition = $this->getCurrentPosition($entity);
                        $identifier = $entityIds[++$currentPosition];
                    }
                    break;
                case self::LAST:
                    if ($currentId != end($entityIds)) {
                        $identifier = end($entityIds);
                    }
                    break;
            }
        }

        return $identifier;

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
     * @return bool
     */
    protected function isEntityInStorage($entity)
    {
        $storage = $this->getStorage();
        if (null === $storage) {
            return false;
        }

        $entityName = $this->getName($entity);
        $identifierValue = $this->getIdentifierValue($entity);

        return !empty($storage[$entityName][self::ENTITY_IDS])
            && in_array($identifierValue, $storage[$entityName][self::ENTITY_IDS]);
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
     * @return array
     */
    protected function getEntityIds($entity)
    {
        $entityName = $this->getName($entity);
        $storage = $this->getStorage();
        $entityIds = [];
        if ($storage && !empty($storage[$entityName][self::ENTITY_IDS])) {
            $entityIds = $storage[$entityName][self::ENTITY_IDS];
        }

        return $entityIds;
    }

    /**
     * @param object $entity
     * @return int
     */
    protected function getCurrentPosition($entity)
    {
        return array_search(
            $this->getIdentifierValue($entity),
            $this->getEntityIds($entity)
        );
    }
}
