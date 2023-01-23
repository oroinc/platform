<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Security\UserLoaderInterface;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * Provides a set of methods to simplify manage of the User entity.
 */
class UserManager extends BaseUserManager
{
    public const STATUS_ACTIVE  = 'active';
    public const STATUS_RESET = 'reset';

    private const AUTH_STATUS_ENUM_CODE = 'auth_status';

    /** @var EnumValueProvider */
    private $enumValueProvider;

    /** @var ServiceLink */
    private $emailProcessorLink;

    public function __construct(
        UserLoaderInterface $userLoader,
        ManagerRegistry $doctrine,
        EncoderFactoryInterface $encoderFactory,
        EnumValueProvider $enumValueProvider,
        ServiceLink $emailProcessor
    ) {
        parent::__construct($userLoader, $doctrine, $encoderFactory);
        $this->enumValueProvider = $enumValueProvider;
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
        $user->setAuthStatus($this->enumValueProvider->getEnumValueByCode(self::AUTH_STATUS_ENUM_CODE, $authStatus));
    }

    /**
     * {@inheritdoc}
     */
    public function updateUser(UserInterface $user, bool $flush = true): void
    {
        // make sure user has a default status
        if ($user instanceof User && null === $user->getAuthStatus()) {
            $defaultStatus = $this->enumValueProvider->getDefaultEnumValueByCode(self::AUTH_STATUS_ENUM_CODE);
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
