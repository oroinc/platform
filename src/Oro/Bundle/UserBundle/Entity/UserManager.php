<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * Provides a set of methods to simplify manage of the User entity.
 */
class UserManager extends BaseUserManager
{
    const AUTH_STATUS_ENUM_CODE = 'auth_status';
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';

    /** @var EnumValueProvider */
    protected $enumValueProvider;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param string $class
     * @param ManagerRegistry $registry
     * @param EncoderFactoryInterface $encoderFactory
     * @param EnumValueProvider $enumValueProvider
     * @param ConfigManager $configManager
     */
    public function __construct(
        $class,
        ManagerRegistry $registry,
        EncoderFactoryInterface $encoderFactory,
        EnumValueProvider $enumValueProvider,
        ConfigManager $configManager
    ) {
        parent::__construct($class, $registry, $encoderFactory);

        $this->enumValueProvider = $enumValueProvider;
        $this->configManager = $configManager;
    }

    /**
     * Return related repository
     *
     * @param User $user
     * @param Organization $organization
     *
     * @return UserApi
     */
    public function getApi(User $user, Organization $organization)
    {
        return $this->getStorageManager()->getRepository('OroUserBundle:UserApi')->getApi($user, $organization);
    }

    /**
     * Sets AuthStatus with enum value id
     *
     * @param User $user
     * @param string $authStatus EnumValueId
     */
    public function setAuthStatus(User $user, $authStatus)
    {
        $user->setAuthStatus($this->enumValueProvider->getEnumValueByCode(self::AUTH_STATUS_ENUM_CODE, $authStatus));
    }

    /**
     * {@inheritdoc}
     */
    public function updateUser(UserInterface $user, $flush = true)
    {
        // make sure user has a default status
        if ($user instanceof User && null === $user->getAuthStatus()) {
            $defaultStatus = $this->enumValueProvider->getDefaultEnumValuesByCode(self::AUTH_STATUS_ENUM_CODE);
            $defaultStatus = is_array($defaultStatus) ? reset($defaultStatus) : $defaultStatus;

            $user->setAuthStatus($defaultStatus);
        }

        return parent::updateUser($user, $flush);
    }

    /**
     * Generates a random string that can be used as a password for a user.
     *
     * @param int $maxLength
     *
     * @return string
     */
    public function generatePassword($maxLength = 30)
    {
        return str_shuffle(
            substr(
                sprintf(
                    '%s%s%s',
                    // get one random upper case letter
                    substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1),
                    // get one random digit
                    substr(str_shuffle('1234567890'), 0, 1),
                    // get some random string
                    strtr(base64_encode(hash('sha256', uniqid((string)mt_rand(), true), true)), '+/=', '___')
                ),
                0,
                $maxLength
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function assertRoles(UserInterface $user)
    {
        if (count($user->getRoles()) === 0) {
            $storageManager = $this->getStorageManager();

            $roleClassName = $storageManager
                ->getClassMetadata(ClassUtils::getClass($user))
                ->getAssociationTargetClass('roles');
            if (!is_a($roleClassName, RoleInterface::class, true)) {
                throw new \RuntimeException(
                    sprintf('Expected %s, %s given', RoleInterface::class, $roleClassName)
                );
            }

            /** @var RoleInterface|null $role */
            $role = $storageManager
                ->getRepository($roleClassName)
                ->findOneBy(['role' => User::ROLE_DEFAULT]);
            if (!$role) {
                throw new \RuntimeException('Default user role not found');
            }

            $user->addRole($role);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isCaseInsensitiveEmailAddressesEnabled(): bool
    {
        return (bool) $this->configManager->get('oro_user.case_insensitive_email_addresses_enabled');
    }
}
