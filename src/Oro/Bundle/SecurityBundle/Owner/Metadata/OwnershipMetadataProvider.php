<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * This class provides access to the ownership metadata of a domain object
 */
class OwnershipMetadataProvider extends AbstractMetadataProvider
{
    /**
     * @var string
     */
    protected $organizationClass;

    /**
     * @var string
     */
    protected $businessUnitClass;

    /**
     * @var string
     */
    protected $userClass;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * Constructor
     *
     * @param array               $owningEntityNames
     * @param ConfigProvider      $configProvider
     * @param EntityClassResolver $entityClassResolver
     * @param CacheProvider|null  $cache
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function __construct(
        array $owningEntityNames,
        ConfigProvider $configProvider,
        EntityClassResolver $entityClassResolver = null,
        CacheProvider $cache = null
    ) {
        $this->organizationClass = $entityClassResolver === null
            ? $owningEntityNames['organization']
            : $entityClassResolver->getEntityClass($owningEntityNames['organization']);
        $this->businessUnitClass = $entityClassResolver === null
            ? $owningEntityNames['business_unit']
            : $entityClassResolver->getEntityClass($owningEntityNames['business_unit']);
        $this->userClass         = $entityClassResolver === null
            ? $owningEntityNames['user']
            : $entityClassResolver->getEntityClass($owningEntityNames['user']);

        $this->configProvider = $configProvider;
        $this->cache          = $cache;

        $this->noOwnershipMetadata = new OwnershipMetadata();
    }

    /**
     * @param SecurityFacade $securityFacade
     *
     * @return MetadataProviderInterface
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSystemLevelClass()
    {
        throw new \BadMethodCallException('Method getSystemLevelClass() unsupported.');
    }

    /**
     * {@inheritDoc}
     */
    public function getGlobalLevelClass()
    {
        return $this->organizationClass;
    }

    /**
     * Gets the class name of the organization entity
     *
     * @return string
     *
     * @deprecated since 1.8, use getGlobalLevelClass instead
     */
    public function getOrganizationClass()
    {
        return $this->getGlobalLevelClass();
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalLevelClass($deep = false)
    {
        return $this->businessUnitClass;
    }

    /**
     * Gets the class name of the business unit entity
     *
     * @return string
     *
     * @deprecated since 1.8, use getLocalLevelClass instead
     */
    public function getBusinessUnitClass()
    {
        return $this->getLocalLevelClass();
    }

    /**
     * {@inheritDoc}
     */
    public function getBasicLevelClass()
    {
        return $this->userClass;
    }

    /**
     * Gets the class name of the user entity
     *
     * @return string
     *
     * @deprecated since 1.8, use getBasicLevelClass instead
     */
    public function getUserClass()
    {
        return $this->getBasicLevelClass();
    }

    /**
     * {@inheritDoc}
     */
    public function supports()
    {
        return $this->securityFacade && $this->securityFacade->getLoggedUser() instanceof User;
    }

    /**
     * {@inheritDoc}
     */
    protected function getOwnershipMetadata(ConfigInterface $config)
    {
        $ownerType              = $config->get('owner_type');
        $ownerFieldName         = $config->get('owner_field_name');
        $ownerColumnName        = $config->get('owner_column_name');
        $organizationFieldName  = $config->get('organization_field_name');
        $organizationColumnName = $config->get('organization_column_name');

        if (!$organizationFieldName && $ownerType == OwnershipType::OWNER_TYPE_ORGANIZATION) {
            $organizationFieldName  = $ownerFieldName;
            $organizationColumnName = $ownerColumnName;
        }

        $data = new OwnershipMetadata(
            $ownerType,
            $ownerFieldName,
            $ownerColumnName,
            $organizationFieldName,
            $organizationColumnName
        );

        return $data;
    }
}
