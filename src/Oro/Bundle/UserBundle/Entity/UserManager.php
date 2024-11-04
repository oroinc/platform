<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Security\UserLoaderInterface;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * Provides a set of methods to simplify manage of the User entity.
 */
class UserManager extends BaseUserManager
{
    public const STATUS_ACTIVE  = 'active';
    public const STATUS_RESET = 'reset';
    public const AUTH_STATUS_ENUM_CODE = 'auth_status';

    /** @var EnumOptionsProvider */
    private $enumOptionsProvider;

    /** @var ServiceLink */
    private $emailProcessorLink;

    public function __construct(
        UserLoaderInterface $userLoader,
        ManagerRegistry $doctrine,
        PasswordHasherFactoryInterface $passwordHasherFactory,
        EnumOptionsProvider $enumOptionsProvider,
        ServiceLink $emailProcessor
    ) {
        parent::__construct($userLoader, $doctrine, $passwordHasherFactory);
        $this->enumOptionsProvider = $enumOptionsProvider;
        $this->emailProcessorLink = $emailProcessor;
    }

    /**
     * Return UserApi entity for the given user and organization
     */
    public function getApi(User $user, Organization $organization): ?UserApi
    {
        return $this->getEntityManager()->getRepository(UserApi::class)->getApi($user, $organization);
    }

    /**
     * Sets the given authentication status for a user
     */
    public function setAuthStatus(User $user, string $authStatus): void
    {
        $user->setAuthStatus($this->enumOptionsProvider->getEnumOptionByCode(self::AUTH_STATUS_ENUM_CODE, $authStatus));
    }

    #[\Override]
    public function updateUser(UserInterface $user, bool $flush = true): void
    {
        // make sure user has a default status
        if ($user instanceof User && null === $user->getAuthStatus()) {
            $defaultStatus = $this->enumOptionsProvider->getDefaultEnumOptionByCode(self::AUTH_STATUS_ENUM_CODE);
            if (null !== $defaultStatus) {
                $user->setAuthStatus($defaultStatus);
            }
        }

        parent::updateUser($user, $flush);
    }

    public function sendResetPasswordEmail(User $user): void
    {
        $user->setConfirmationToken($user->generateToken());
        $this->getEmailProcessor()->sendResetPasswordEmail($user);
        $user->setPasswordRequestedAt(new \DateTime('now', new \DateTimeZone('UTC')));
    }

    private function getEmailProcessor(): Processor
    {
        return $this->emailProcessorLink->getService();
    }
}
