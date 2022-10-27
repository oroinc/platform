<?php

namespace Oro\Bundle\SecurityBundle\Authorization;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides a set of methods to simplify checking access in controllers.
 */
class RequestAuthorizationChecker
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private EntityClassResolver $entityClassResolver;
    private AclAnnotationProvider $annotationProvider;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        EntityClassResolver $entityClassResolver,
        AclAnnotationProvider $annotationProvider
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->entityClassResolver = $entityClassResolver;
        $this->annotationProvider = $annotationProvider;
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
        $aclAnnotation = $this->getRequestAcl($request, true);
        if ($aclAnnotation) {
            $class = $aclAnnotation->getClass();
            $permission = $aclAnnotation->getPermission();
            if ($permission && $class && is_a($object, $class)) {
                return $this->authorizationChecker->isGranted($permission, $object) ? 1 : -1;
            }
        }

        return 0;
    }

    /**
     * Gets ACL annotation for a controller action which was taken from the given request object.
     */
    public function getRequestAcl(Request $request, bool $convertClassName = false): ?AclAnnotation
    {
        $controller = $request->attributes->get('_controller');
        if (str_contains($controller, '::')) {
            [$controllerClass, $controllerMethod] = explode('::', $controller);
        } else {
            $controllerClass = $controller;
            $controllerMethod = '__invoke';
        }

        $annotation = $this->annotationProvider->findAnnotation($controllerClass, $controllerMethod);
        if ($convertClassName && null !== $annotation) {
            $entityClass = $annotation->getClass();
            if ($entityClass && $this->entityClassResolver->isEntity($entityClass)) {
                $annotation->setClass($this->entityClassResolver->getEntityClass($entityClass));
            }
        }

        return $annotation;
    }
}
