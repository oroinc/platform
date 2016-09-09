<?php

namespace Oro\Bundle\SecurityBundle\Filter;

use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Doctrine\ORM\EntityManagerInterface;

use Oro\Component\EntitySerializer\DoctrineHelper;
use Oro\Component\EntitySerializer\Filter\EntityAwareFilterInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class SerializerFieldFilter implements EntityAwareFilterInterface
{
    /** @var AuthorizationCheckerInterface */
    protected $authChecker;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigProvider security namespace config provider */
    protected $configProvider;

    /** @var array|ConfigInterface[] */
    protected $securityConfigs = [];

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ConfigProvider $securityConfigProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->authChecker = $authorizationChecker;
        $this->configProvider = $securityConfigProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function checkField($entity, $entityClass, $field)
    {
        if (is_array($entity) && !empty($entity['entityId'])) {
            $entity = $this->getEntityReference($entityClass, $entity['entityId']);
        }

        $isFieldAllowed = $this->authChecker->isGranted('VIEW', new FieldVote($entity, $field));
        $shouldShowRestricted = $this->shouldShowRestricted($entityClass);

        if (!$shouldShowRestricted && !$isFieldAllowed) {
            return static::FILTER_ALL; // field's value will be filtered with field
        }

        if ($isFieldAllowed) {
            return static::FILTER_NOTHING; // field will be shown with it's value
        }

        return static::FILTER_VALUE; // field will be shown, but without value, e.g. null
    }

    /**
     * @param string $entityClass
     *
     * @return bool returns false only if it's explicitly set in entity config
     */
    protected function shouldShowRestricted($entityClass)
    {
        $securityConfig = $this->getSecurityConfig($entityClass);

        return $securityConfig ? $securityConfig->get('show_restricted_fields', false, /* default */ true) : true;
    }

    /**
     * @param string $className
     *
     * @return ConfigInterface|null
     */
    protected function getSecurityConfig($className)
    {
        if (empty($this->securityConfigs[$className])) {
            try {
                $this->securityConfigs[$className] = $this->configProvider->getConfig($className);
            } catch (RuntimeException $e) {
                $this->securityConfigs[$className] = null;
            }
        }

        return $this->securityConfigs[$className];
    }

    /**
     * @param string     $entityClass
     * @param string|int $identifier
     *
     * @return object
     */
    protected function getEntityReference($entityClass, $identifier)
    {
        static $entityManagers = [];

        if (empty($entityManagers[$entityClass])) {
            $entityManagers[$entityClass] = $this->doctrineHelper->getEntityManager($entityClass);
        }

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $entityManagers[$entityClass];

        return $entityManager->getReference($entityClass, $identifier);
    }
}
