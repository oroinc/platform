<?php

namespace Oro\Bundle\SecurityBundle;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Oro\Bundle\SecurityBundle\Authorization\RequestAuthorizationChecker;

/**
 * @deprecated since 2.3
 */
class SecurityFacade
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var RequestAuthorizationChecker */
    private $requestAuthorizationChecker;

    /** @var ClassAuthorizationChecker */
    private $classAuthorizationChecker;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param RequestAuthorizationChecker   $requestAuthorizationChecker
     * @param ClassAuthorizationChecker     $classAuthorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        RequestAuthorizationChecker $requestAuthorizationChecker,
        ClassAuthorizationChecker $classAuthorizationChecker,
        TokenStorageInterface $tokenStorage
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->requestAuthorizationChecker = $requestAuthorizationChecker;
        $this->classAuthorizationChecker = $classAuthorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return null|TokenInterface
     * @deprecated since 2.3. Use TokenAccessorInterface::getToken instead
     */
    public function getToken()
    {
        return $this->tokenStorage->getToken();
    }

    /**
     * Checks if an access to the given method of the given class is granted to the caller
     *
     * @param  string $class
     * @param  string $method
     * @return bool
     * @deprecated since 2.3. Use ClassAuthorizationChecker::isClassMethodGranted instead
     */
    public function isClassMethodGranted($class, $method)
    {
        return $this->classAuthorizationChecker->isClassMethodGranted($class, $method);
    }

    /**
     * Gets ACL annotation is bound to the given class/method
     *
     * @param string $class
     * @param string $method
     * @return Acl|null
     * @deprecated since 2.3. Use ClassAuthorizationChecker::getClassMethodAnnotation instead
     */
    public function getClassMethodAnnotation($class, $method)
    {
        return $this->classAuthorizationChecker->getClassMethodAnnotation($class, $method);
    }

    /**
     * Checks if an access to a resource is granted to the caller
     *
     * @param string|string[] $attributes Can be a role name(s), permission name(s), an ACL annotation id,
     *                                    string in format "permission;descriptor"
     *                                    (VIEW;entity:AcmeDemoBundle:AcmeEntity, EDIT;action:acme_action)
     *                                    or something else, it depends on registered security voters
     * @param  mixed          $object     A domain object, object identity or object identity descriptor (id:type)
     *                                    (entity:Acme/DemoBundle/Entity/AcmeEntity,  action:some_action)
     *
     * @return bool
     * @deprecated since 2.3. Use AuthorizationCheckerInterface::isGranted instead
     */
    public function isGranted($attributes, $object = null)
    {
        return $this->authorizationChecker->isGranted($attributes, $object);
    }

    /**
     * Gets logged user object or null
     *
     * @return mixed|null
     * @deprecated since 2.3. Use TokenAccessorInterface::getUser instead
     */
    public function getLoggedUser()
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }

    /**
     * Gets id of currently logged in user.
     *
     * @return int 0 if there is not currently logged in user; otherwise, a number greater than zero
     * @deprecated since 2.3. Use TokenAccessorInterface::getUserId instead
     */
    public function getLoggedUserId()
    {
        $user = $this->getLoggedUser();
        return $user ? $user->getId() : 0;
    }

    /**
     * Checks whether any user is currently logged in or not
     *
     * @return bool
     * @deprecated since 2.3. Use "if (null !== TokenAccessorInterface::getUser())" instead
     */
    public function hasLoggedUser()
    {
        return ($this->getLoggedUser() !== null);
    }

    /**
     * Get current organization object from the security token
     *
     * @return bool|Organization
     * @deprecated since 2.3. Use TokenAccessorInterface::getOrganization instead
     */
    public function getOrganization()
    {
        $token = $this->tokenStorage->getToken();
        if ($token instanceof OrganizationContextTokenInterface) {
            return $token->getOrganizationContext();
        }

        return false;
    }

    /**
     * Get current organization id from the security token
     *
     * @return int|null
     * @deprecated since 2.3. Use TokenAccessorInterface::getOrganizationId instead
     */
    public function getOrganizationId()
    {
        /** @var Organization $organization */
        $organization = $this->getOrganization();
        return $organization ? $organization->getId() : null;
    }

    /**
     * Get ACL annotation object for current controller action which was taken from request object
     *
     * @param Request $request
     * @param bool    $convertClassName
     * @return null|Acl
     * @deprecated since 2.3. Use RequestAuthorizationChecker::getRequestAcl instead
     */
    public function getRequestAcl(Request $request, $convertClassName = false)
    {
        return $this->requestAuthorizationChecker->getRequestAcl($request, $convertClassName);
    }

    /**
     * Check access for object for current controller action which was taken from request object
     *
     * @param Request $request
     * @param         $object
     * @return int -1 if no access, 0 if can't decide, 1 if access is granted
     * @deprecated since 2.3. Use RequestAuthorizationChecker::isRequestObjectIsGranted instead
     */
    public function isRequestObjectIsGranted(Request $request, $object)
    {
        return $this->requestAuthorizationChecker->isRequestObjectIsGranted($request, $object);
    }
}
