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
    public function __construct(
        private ManagerRegistry $doctrine
    ) {
    }

    #[\Override]
    public function serialize(TokenInterface $token): string
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

    #[\Override]
    public function deserialize(string $value): TokenInterface
    {
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
        /** @var Organization|null $organization */
        $organization = $this->loadEntity(Organization::class, $organizationId);
        if (null === $organization) {
            throw new InvalidTokenUserOrganizationException(
                'An error occurred while creating a token: organization not found.'
            );
        }

        /** @var AbstractUser|null $user */
        $user = $this->loadEntity($userClass, $userId);
        if (null === $user) {
            throw new InvalidTokenUserOrganizationException(
                'An error occurred while creating a token: user not found.'
            );
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

    private function loadEntity(string $entityClass, mixed $entityId): ?object
    {
        $em = $this->doctrine->getManagerForClass($entityClass);
        $entity = $em->find($entityClass, $entityId);
        if (null !== $entity) {
            $em->refresh($entity);
        }

        return $entity;
    }
}
