<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;

class WorkflowEntityConnector
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var WorkflowSystemConfigManager */
    protected $workflowConfigManager;

    /** @var array */
    protected $supportedIdentifierTypes = [
        Type::BIGINT,
        Type::DECIMAL,
        Type::INTEGER,
        Type::SMALLINT,
        Type::STRING
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

        if (!array_key_exists($entityClass, $this->cache)) {
            $this->cache[$entityClass] = $this->workflowConfigManager->isConfigurable($entityClass) &&
                $this->isSupportedIdentifierType($entityClass);
        }

        return $this->cache[$entityClass];
    }

    /**
     * Not supports composed Primary Keys (more than one identifier field)
     * Supports only list of specified in $supportedIdentifierTypes
     *
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

        $type = $metadata->getTypeOfField($identifier[0]);

        return count($identifier) === 1 &&
            in_array($type instanceof Type ? $type->getName() : (string)$type, $this->supportedIdentifierTypes, true);
    }
}
