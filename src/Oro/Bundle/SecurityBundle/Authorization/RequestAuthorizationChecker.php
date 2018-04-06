<?php

namespace Oro\Bundle\SecurityBundle\Authorization;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RequestAuthorizationChecker
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var ServiceLink */
    private $entityClassResolverLink;

    /** @var ServiceLink */
    private $annotationProviderLink;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ServiceLink                   $entityClassResolverLink
     * @param ServiceLink                   $annotationProviderLink
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ServiceLink $entityClassResolverLink,
        ServiceLink $annotationProviderLink
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->entityClassResolverLink = $entityClassResolverLink;
        $this->annotationProviderLink = $annotationProviderLink;
    }

    /**
     * Check access for object for current controller action which was taken from request object.
     *
     * @param Request $request
     * @param mixed   $object
     *
     * @return int -1 if no access, 0 if can't decide, 1 if access is granted
     */
    public function isRequestObjectIsGranted(Request $request, $object)
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
     * Get ACL annotation object for current controller action which was taken from request object.
     *
     * @param Request $request
     * @param bool    $convertClassName
     *
     * @return AclAnnotation|null
     */
    public function getRequestAcl(Request $request, $convertClassName = false)
    {
        $controller = $request->attributes->get('_controller');
        if (false === strpos($controller, '::')) {
            return null;
        }

        $controllerData = explode('::', $controller);
        $annotation = $this->getAnnotation($controllerData[0], $controllerData[1]);
        if ($convertClassName && null !== $annotation) {
            $entityClass = $annotation->getClass();
            if ($entityClass) {
                /** @var EntityClassResolver $entityClassResolver */
                $entityClassResolver = $this->entityClassResolverLink->getService();
                if ($entityClassResolver->isEntity($entityClass)) {
                    $annotation->setClass($entityClassResolver->getEntityClass($entityClass));
                }
            }
        }

        return $annotation;
    }

    /**
     * @param string      $class
     * @param string|null $method
     *
     * @return AclAnnotation|null
     */
    private function getAnnotation($class, $method = null)
    {
        /** @var AclAnnotationProvider $annotationProvider */
        $annotationProvider = $this->annotationProviderLink->getService();

        return $annotationProvider->findAnnotation($class, $method);
    }
}
