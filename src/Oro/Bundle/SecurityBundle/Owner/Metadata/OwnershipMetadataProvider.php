<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * This class provides access to the ownership metadata of a domain object
 */
class OwnershipMetadataProvider extends AbstractOwnershipMetadataProvider
{
    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var CacheProvider */
    private $cache;

    /** @var array */
    private $owningEntityNames;

    /** @var string */
    private $organizationClass;

    /** @var string */
    private $businessUnitClass;

    /** @var string */
    private $userClass;

    /**
     * @param array                  $owningEntityNames [owning entity type => entity class name, ...]
     * @param ConfigManager          $configManager
     * @param EntityClassResolver    $entityClassResolver
     * @param TokenAccessorInterface $tokenAccessor
     * @param CacheProvider          $cache
     */
    public function __construct(
        array $owningEntityNames,
        ConfigManager $configManager,
        EntityClassResolver $entityClassResolver,
        TokenAccessorInterface $tokenAccessor,
        CacheProvider $cache
    ) {
        parent::__construct($configManager);
        $this->owningEntityNames = $owningEntityNames;
        $this->entityClassResolver = $entityClassResolver;
        $this->tokenAccessor = $tokenAccessor;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserClass()
    {
        $this->ensureOwningEntityClassesInitialized();

        return $this->userClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getBusinessUnitClass()
    {
        $this->ensureOwningEntityClassesInitialized();

        return $this->businessUnitClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganizationClass()
    {
        $this->ensureOwningEntityClassesInitialized();

        return $this->organizationClass;
    }

    /**
     * {@inheritdoc}
     */
    public function supports()
    {
        return $this->tokenAccessor->getUser() instanceof User;
    }

    /**
     * Fix Access Level for given object. Change it from SYSTEM_LEVEL to GLOBAL_LEVEL
     * if object have owner type OWNER_TYPE_BUSINESS_UNIT, OWNER_TYPE_USER or OWNER_TYPE_ORGANIZATION
     *
     * {@inheritdoc}
     */
    public function getMaxAccessLevel($accessLevel, $className = null)
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

    /**
     * {@inheritdoc}
     */
    protected function getCache()
    {
        return $this->cache;
    }

    /**
     * {@inheritdoc}
     */
    protected function createNoOwnershipMetadata()
    {
        return new OwnershipMetadata();
    }

    /**
     * {@inheritdoc}
     */
    protected function getOwnershipMetadata(ConfigInterface $config)
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
    private function ensureOwningEntityClassesInitialized()
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
