<?php

namespace Oro\Bundle\SecurityBundle\Authentication;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\ImpersonationToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;

class TokenSerializer implements TokenSerializerInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(TokenInterface $token)
    {
        if ($token instanceof OrganizationContextTokenInterface) {
            $user = $token->getUser();
            if ($user instanceof AbstractUser) {
                return sprintf(
                    'organizationId=%d;userId=%d;userClass=%s;roles=%s',
                    $token->getOrganizationContext()->getId(),
                    $user->getId(),
                    ClassUtils::getRealClass($user),
                    $this->packRoles($token)
                );
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize($value)
    {
        if (!$value) {
            return null;
        }

        $unpacked = $this->unpack($value);
        if (null === $unpacked) {
            return null;
        }

        list($organizationId, $userId, $userClass, $roles) = $unpacked;

        return $this->createToken($organizationId, $userId, $userClass, $roles);
    }

    /**
     * @param TokenInterface $token
     *
     * @return string
     */
    private function packRoles(TokenInterface $token)
    {
        return implode(
            ',',
            array_map(
                function (RoleInterface $role) {
                    return $role->getRole();
                },
                $token->getRoles()
            )
        );
    }

    /**
     * @param string $value organizationId=int;userId=int;userClass=string;roles=string,...
     *
     * @return array|null [organizationId, userId, userClass, roles]
     */
    private function unpack($value)
    {
        $result = null;
        $elements = $this->unpackElements($value);
        if (count($elements) === 4) {
            $organizationId = null;
            $userId = null;
            $userClass = null;
            $roles = null;
            if (array_key_exists('organizationId', $elements)) {
                $organizationId = (int)$elements['organizationId'];
            }
            if (array_key_exists('userId', $elements)) {
                $userId = (int)$elements['userId'];
            }
            if (array_key_exists('userClass', $elements)) {
                $userClass = $elements['userClass'];
            }
            if (array_key_exists('roles', $elements)) {
                $roles = explode(',', $elements['roles']);
            }
            if (null !== $organizationId && null !== $userId && null !== $userClass && null !== $roles) {
                $result = [$organizationId, $userId, $userClass, $roles];
            }
        }

        return $result;
    }

    /**
     * @param string $value
     *
     * @return array
     */
    private function unpackElements($value)
    {
        $elements = [];
        $parts = explode(';', $value);
        if (count($parts) === 4) {
            foreach ($parts as $part) {
                $items = explode('=', $part);
                if (count($items) === 2) {
                    $elements[$items[0]] = $items[1];
                }
            }
        }

        return $elements;
    }

    /**
     * @param int      $organizationId
     * @param int      $userId
     * @param string   $userClass
     * @param string[] $roles
     *
     * @return TokenInterface|null
     */
    private function createToken($organizationId, $userId, $userClass, array $roles)
    {
        $organization = $this->doctrine->getRepository(Organization::class)->find($organizationId);

        /** @var AbstractUser $user */
        $user = $this->doctrine->getRepository($userClass)->find($userId);

        if (null === $organization || null === $user) {
            return null;
        }

        $roleObjects = [];
        $allRoles = $user->getRoles();
        foreach ($allRoles as $role) {
            if (in_array($role->getRole(), $roles, true)) {
                $roleObjects[] = $role;
            }
        }

        return new ImpersonationToken($user, $organization, $roleObjects);
    }
}
