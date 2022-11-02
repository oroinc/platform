<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Provide information about entity applicability for workflow.
 * Only entities with single identifiers of (big|small)int, decimal and string are supported.
 */
class WorkflowEntityConnector
{
    private const WORKFLOW_APPLICABLE_ENTITIES_CACHE_KEY_PREFIX = 'workflow_applicable_entity:';

    protected ManagerRegistry $registry;
    protected array $supportedIdentifierTypes = [
        Types::BIGINT,
        Types::DECIMAL,
        Types::INTEGER,
        Types::SMALLINT,
        Types::STRING
    ];
    protected CacheInterface $cache;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param CacheInterface $cache
     */
    public function __construct(ManagerRegistry $managerRegistry, ?CacheInterface $cache = null)
    {
        $this->registry = $managerRegistry;
        $this->cache = $cache ?: new ArrayAdapter(0, false);
    }

    public function isApplicableEntity(object|string $entity): bool
    {
        $entityClass = is_object($entity) ? ClassUtils::getClass($entity) : ClassUtils::getRealClass($entity);
        $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey(
            self::WORKFLOW_APPLICABLE_ENTITIES_CACHE_KEY_PREFIX . $entityClass
        );
        return $this->cache->get($cacheKey, function () use ($entityClass) {
            return $this->isSupportedIdentifierType($entityClass);
        });
    }

    /**
     * Not supports composed Primary Keys (more than one identifier field)
     * Supports only list of specified in $supportedIdentifierTypes
     */
    protected function isSupportedIdentifierType(string $class): bool
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
