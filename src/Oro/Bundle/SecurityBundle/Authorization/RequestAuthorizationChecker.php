<?php

namespace Oro\Bundle\SecurityBundle\Authorization;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Attribute\Acl as AclAttribute;
use Oro\Bundle\SecurityBundle\Metadata\AclAttributeProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides a set of methods to simplify checking access in controllers.
 */
class RequestAuthorizationChecker
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private EntityClassResolver $entityClassResolver;
    private AclAttributeProvider $attributeProvider;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        EntityClassResolver $entityClassResolver,
        AclAttributeProvider $attributeProvider
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->entityClassResolver = $entityClassResolver;
        $this->attributeProvider = $attributeProvider;
    }

    /**
     * Checks if an access to the given object is granted for a controller action
     * which was taken from the given request object.
     *
     * @param Request $request
     * @param mixed   $object
     *
     * @return int -1 if no access, 0 if can't decide, 1 if access is granted
     */
    public function isRequestObjectIsGranted(Request $request, mixed $object): int
    {
        $aclAttribute = $this->getRequestAcl($request, true);
        if ($aclAttribute) {
            $class = $aclAttribute->getClass();
            $permission = $aclAttribute->getPermission();
            if ($permission && $class && is_a($object, $class)) {
                return $this->authorizationChecker->isGranted($permission, $object) ? 1 : -1;
            }
        }

        return 0;
    }

    /**
     * Gets ACL attribute for a controller action which was taken from the given request object.
     */
    public function getRequestAcl(Request $request, bool $convertClassName = false): ?AclAttribute
    {
        $controller = $request->attributes->get('_controller');
        if (str_contains($controller, '::')) {
            [$controllerClass, $controllerMethod] = explode('::', $controller);
        } else {
            $controllerClass = $controller;
            $controllerMethod = '__invoke';
        }

        $attribute = $this->attributeProvider->findAttribute($controllerClass, $controllerMethod);
        if ($convertClassName && null !== $attribute) {
            $entityClass = $attribute->getClass();
            if ($entityClass && $this->entityClassResolver->isEntity($entityClass)) {
                $attribute->setClass($this->entityClassResolver->getEntityClass($entityClass));
            }
        }

        return $attribute;
    }
}
