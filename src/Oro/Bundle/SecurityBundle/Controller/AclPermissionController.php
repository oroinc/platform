<?php

namespace Oro\Bundle\SecurityBundle\Controller;

use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller that provides ACL access levels for a specific domain object.
 */
class AclPermissionController
{
    /** @var EntityRoutingHelper */
    private $entityRoutingHelper;

    /** @var AclManager */
    private $aclManager;

    public function __construct(EntityRoutingHelper $entityRoutingHelper, AclManager $aclManager)
    {
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->aclManager = $aclManager;
    }

    /**
     * @Route(
     *      "/acl-access-levels/{oid}/{permission}",
     *      name="oro_security_access_levels",
     *      requirements={"oid"="[\w]+:[\w\:\(\)\|]+", "permission"="[\w/]+"},
     *      defaults={"_format"="json", "permission"=null}
     * )
     * @Template
     */
    public function aclAccessLevelsAction(string $oid, string $permission = null): array
    {
        if (ObjectIdentityHelper::getExtensionKeyFromIdentityString($oid) === EntityAclExtension::NAME) {
            $entity = ObjectIdentityHelper::getClassFromIdentityString($oid);
            if (ObjectIdentityFactory::ROOT_IDENTITY_TYPE !== $entity) {
                if (ObjectIdentityHelper::isFieldEncodedKey($entity)) {
                    [$className, $fieldName] = ObjectIdentityHelper::decodeEntityFieldInfo($entity);
                    $oid = ObjectIdentityHelper::encodeIdentityString(
                        EntityAclExtension::NAME,
                        ObjectIdentityHelper::encodeEntityFieldInfo(
                            $this->entityRoutingHelper->resolveEntityClass($className),
                            $fieldName
                        )
                    );
                } else {
                    $oid = ObjectIdentityHelper::encodeIdentityString(
                        EntityAclExtension::NAME,
                        $this->entityRoutingHelper->resolveEntityClass($entity)
                    );
                }
            }
        }

        return ['levels' => $this->aclManager->getAccessLevels($oid, $permission)];
    }
}
