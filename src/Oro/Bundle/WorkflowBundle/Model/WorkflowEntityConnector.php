<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Cache;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;

class WorkflowEntityConnector
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var WorkflowSystemConfigManager */
    protected $workflowConfigManager;

    protected $supportedIdentifierTypes = [
        Type::BIGINT,
        Type::DECIMAL,
        Type::INTEGER,
        Type::SMALLINT,
        Type::STRING,
        Type::TEXT
    ];

    /** @var array|bool[] */
    protected $cache = [];

    /**
     * @param ManagerRegistry $managerRegistry
     * @param WorkflowSystemConfigManager $workflowConfigManager
     */
    public function __construct(ManagerRegistry $managerRegistry, WorkflowSystemConfigManager $workflowConfigManager)
    {
        $this->registry = $managerRegistry;
        $this->workflowConfigManager = $workflowConfigManager;
    }

    /**
     * @param object|string $entity Entity object or its class name
     * @return bool
     */
    public function isApplicableEntity($entity)
    {
        $entityClass = is_object($entity) ? ClassUtils::getClass($entity) : ClassUtils::getRealClass($entity);

        if (array_key_exists($entityClass, $this->cache)) {
            return $this->cache[$entityClass];
        }

        return $this->cache[$entityClass] = $this->check($entityClass);
    }

    /**
     * @param $entityClass
     * @return bool
     */
    protected function check($entityClass)
    {
        if (!$this->workflowConfigManager->isConfigurable($entityClass)) {
            return false;
        }

        if (!$this->isSupportedIdentifierType($entityClass)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $class
     * @return bool
     */
    protected function isSupportedIdentifierType($class)
    {
        $manager = $this->registry->getManagerForClass($class);

        if (null === $manager) {
            throw new NotManageableEntityException($class);
        }

        $metadata = $manager->getClassMetadata($class);

        $identifier = $metadata->getIdentifierFieldNames();

        /*
         * Not supports composed Primary Keys (more than one identifier field)
         * Supports only list of specified in $supportedIdentifierTypes
         */
        return count($identifier) === 1 && in_array(
            (string)$metadata->getTypeOfField($identifier[0]),
            $this->supportedIdentifierTypes,
            true
        );
    }
}
