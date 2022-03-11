<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * This class provides access to the ownership metadata of a domain object
 */
class OwnershipMetadataProvider extends AbstractOwnershipMetadataProvider
{
    protected EntityClassResolver $entityClassResolver;
    protected TokenAccessorInterface $tokenAccessor;
    private CacheInterface $cache;
    private ?array $owningEntityNames;
    private string $organizationClass;
    private string $businessUnitClass;
    private string $userClass;

    /**
     * @param array                  $owningEntityNames [owning entity type => entity class name, ...]
     * @param ConfigManager          $configManager
     * @param EntityClassResolver    $entityClassResolver
     * @param TokenAccessorInterface $tokenAccessor
     * @param CacheInterface          $cache
     */
    public function __construct(
        array $owningEntityNames,
        ConfigManager $configManager,
        EntityClassResolver $entityClassResolver,
        TokenAccessorInterface $tokenAccessor,
        CacheInterface $cache
    ) {
        parent::__construct($configManager);
        $this->owningEntityNames = $owningEntityNames;
        $this->entityClassResolver = $entityClassResolver;
        $this->tokenAccessor = $tokenAccessor;
        $this->cache = $cache;
    }

    public function getUserClass(): string
    {
        $this->ensureOwningEntityClassesInitialized();

        return $this->userClass;
    }

    public function getBusinessUnitClass(): string
    {
        $this->ensureOwningEntityClassesInitialized();

        return $this->businessUnitClass;
    }

    public function getOrganizationClass(): string
    {
        $this->ensureOwningEntityClassesInitialized();

        return $this->organizationClass;
    }

    public function supports(): bool
    {
        return $this->tokenAccessor->getUser() instanceof User;
    }

    /**
     * Fix Access Level for given object. Change it from SYSTEM_LEVEL to GLOBAL_LEVEL
     * if object have owner type OWNER_TYPE_BUSINESS_UNIT, OWNER_TYPE_USER or OWNER_TYPE_ORGANIZATION
     */
    public function getMaxAccessLevel($accessLevel, $className = null): int
    {
        if (AccessLevel::SYSTEM_LEVEL === $accessLevel && $className) {
            $metadata = $this->getMetadata($className);
            if ($metadata->hasOwner()
                && in_array(
                    $metadata->getOwnerType(),
                    [
                        OwnershipMetadata::OWNER_TYPE_BUSINESS_UNIT,
                        OwnershipMetadata::OWNER_TYPE_USER,
                        OwnershipMetadata::OWNER_TYPE_ORGANIZATION
                    ],
                    true
                )
            ) {
                $accessLevel = AccessLevel::GLOBAL_LEVEL;
            }
        }

        return $accessLevel;
    }

    protected function getCache(): CacheInterface
    {
        return $this->cache;
    }

    protected function createNoOwnershipMetadata(): OwnershipMetadataInterface
    {
        return new OwnershipMetadata();
    }

    protected function getOwnershipMetadata(ConfigInterface $config): OwnershipMetadataInterface
    {
        $ownerType = $config->get('owner_type');
        $ownerFieldName = $config->get('owner_field_name');
        $ownerColumnName = $config->get('owner_column_name');
        $organizationFieldName = $config->get('organization_field_name');
        $organizationColumnName = $config->get('organization_column_name');

        if (!$organizationFieldName && $ownerType === OwnershipType::OWNER_TYPE_ORGANIZATION) {
            $organizationFieldName  = $ownerFieldName;
            $organizationColumnName = $ownerColumnName;
        }

        return new OwnershipMetadata(
            $ownerType,
            $ownerFieldName,
            $ownerColumnName,
            $organizationFieldName,
            $organizationColumnName
        );
    }

    /**
     * Makes sure that the owning entity classes are initialized.
     */
    private function ensureOwningEntityClassesInitialized(): void
    {
        if (null === $this->owningEntityNames) {
            // already initialized
            return;
        }

        if (!isset(
            $this->owningEntityNames['organization'],
            $this->owningEntityNames['business_unit'],
            $this->owningEntityNames['user']
        )) {
            throw new \InvalidArgumentException(
                'The $owningEntityNames must contains "organization", "business_unit" and "user" keys.'
            );
        }

        $this->organizationClass = $this->entityClassResolver->getEntityClass(
            $this->owningEntityNames['organization']
        );
        $this->businessUnitClass = $this->entityClassResolver->getEntityClass(
            $this->owningEntityNames['business_unit']
        );
        $this->userClass = $this->entityClassResolver->getEntityClass(
            $this->owningEntityNames['user']
        );

        // remove source data to mark that the initialization passed
        $this->owningEntityNames = null;
    }
}
