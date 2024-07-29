<?php

namespace Oro\Bundle\SecurityBundle\Authentication;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\ImpersonationToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Exception\InvalidTokenSerializationException;
use Oro\Bundle\SecurityBundle\Exception\InvalidTokenUserOrganizationException;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The default implementation of the security token serializer.
 */
class TokenSerializer implements TokenSerializerInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(TokenInterface $token)
    {
        if ($token instanceof OrganizationAwareTokenInterface) {
            $user = $token->getUser();
            if ($user instanceof AbstractUser) {
                return sprintf(
                    'organizationId=%d;userId=%d;userClass=%s;roles=%s',
                    $token->getOrganization()->getId(),
                    $user->getId(),
                    ClassUtils::getRealClass($user),
                    $this->packRoles($token)
                );
            }
        }

        throw new InvalidTokenSerializationException('An error occurred during token serialization.');
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize($value)
    {
        if (!$value) {
            throw new InvalidTokenSerializationException('An error occurred while deserializing the token.');
        }

        $unpacked = $this->unpack($value);
        [$organizationId, $userId, $userClass, $roles] = $unpacked;

        return $this->createToken($organizationId, $userId, $userClass, $roles);
    }

    /**
     * @param TokenInterface $token
     *
     * @return string
     */
    private function packRoles(TokenInterface $token)
    {
        return implode(',', $token->getRoleNames());
    }

    /**
     * @param string $value organizationId=int;userId=int;userClass=string;roles=string,...
     */
    private function unpack(string $value): array
    {
        $elements = $this->unpackElements($value);
        if (count($elements) === 4) {
            $defaultElements = ['organizationId' => null, 'userId' => null, 'userClass' => null, 'roles' => null];
            [$organizationId, $userId, $userClass, $roles] = array_values(array_merge($defaultElements, $elements));
            if (null !== $organizationId && null !== $userId && null !== $userClass && null !== $roles) {
                return [(int)$organizationId, (int)$userId, $userClass, explode(',', $roles)];
            }
        }

        throw new InvalidTokenSerializationException('An error occurred while deserializing the token.');
    }

    private function unpackElements(string $value): array
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

    private function createToken(int $organizationId, int $userId, string $userClass, array $roles): TokenInterface
    {
        $organization = $this->doctrine->getRepository(Organization::class)->find($organizationId);

        /** @var AbstractUser $user */
        $user = $this->doctrine->getRepository($userClass)->find($userId);

        if (null === $organization || null === $user) {
            throw new InvalidTokenUserOrganizationException('An error occurred while creating a token.');
        }

        $roleObjects = [];
        $allRoles = $user->getUserRoles();
        foreach ($allRoles as $role) {
            if (in_array($role->getRole(), $roles, true)) {
                $roleObjects[] = $role;
            }
        }

        return new ImpersonationToken($user, $organization, $roleObjects);
    }
}
