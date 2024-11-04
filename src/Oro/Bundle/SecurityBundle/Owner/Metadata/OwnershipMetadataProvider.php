<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
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
    /** @var array|null [owning entity type => entity class name, ...] */
    private ?array $owningEntityNames;
    private string $organizationClass;
    private string $businessUnitClass;
    private string $userClass;

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

    #[\Override]
    public function supports(): bool
    {
        return $this->tokenAccessor->getUser() instanceof User;
    }

    #[\Override]
    public function getUserClass(): string
    {
        $this->ensureOwningEntityClassesInitialized();

        return $this->userClass;
    }

    #[\Override]
    public function getBusinessUnitClass(): string
    {
        $this->ensureOwningEntityClassesInitialized();

        return $this->businessUnitClass;
    }

    #[\Override]
    public function getOrganizationClass(): ?string
    {
        $this->ensureOwningEntityClassesInitialized();

        return $this->organizationClass;
    }

    #[\Override]
    public function getMaxAccessLevel(int $accessLevel, string $className = null): int
    {
        // Fix Access Level for given object. Change it from SYSTEM_LEVEL to GLOBAL_LEVEL
        // if object have owner type OWNER_TYPE_BUSINESS_UNIT, OWNER_TYPE_USER or OWNER_TYPE_ORGANIZATION.
        if (AccessLevel::SYSTEM_LEVEL === $accessLevel && $className) {
            $metadata = $this->getMetadata($className);
            if ($metadata->hasOwner()
                && (
                    OwnershipMetadata::OWNER_TYPE_BUSINESS_UNIT === $metadata->getOwnerType()
                    || OwnershipMetadata::OWNER_TYPE_USER === $metadata->getOwnerType()
                    || OwnershipMetadata::OWNER_TYPE_ORGANIZATION === $metadata->getOwnerType()
                )
            ) {
                $accessLevel = AccessLevel::GLOBAL_LEVEL;
            }
        }

        return $accessLevel;
    }

    #[\Override]
    protected function getCache(): CacheInterface
    {
        return $this->cache;
    }

    #[\Override]
    protected function createNoOwnershipMetadata(): OwnershipMetadataInterface
    {
        return new OwnershipMetadata();
    }

    #[\Override]
    protected function getOwnershipMetadata(ConfigInterface $config): OwnershipMetadataInterface
    {
        return new OwnershipMetadata(
            $config->get('owner_type', false, ''),
            $config->get('owner_field_name', false, ''),
            $config->get('owner_column_name', false, ''),
            $config->get('organization_field_name', false, ''),
            $config->get('organization_column_name', false, '')
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
