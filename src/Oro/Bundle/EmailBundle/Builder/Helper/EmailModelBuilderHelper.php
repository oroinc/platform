<?php

namespace Oro\Bundle\EmailBundle\Builder\Helper;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Templating\EngineInterface;

use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailModelBuilderHelper
{
    /**
     * @var EntityRoutingHelper
     */
    protected $entityRoutingHelper;

    /**
     * @var EmailAddressHelper
     */
    protected $emailAddressHelper;

    /**
     * @var EntityNameResolver
     */
    protected $entityNameResolver;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var EmailAddressManager
     */
    protected $emailAddressManager;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EmailCacheManager
     */
    protected $emailCacheManager;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var MailboxManager
     */
    protected $mailboxManager;

    /**
     * @param EntityRoutingHelper $entityRoutingHelper
     * @param EmailAddressHelper  $emailAddressHelper
     * @param EntityNameResolver  $entityNameResolver
     * @param SecurityFacade      $securityFacade
     * @param EmailAddressManager $emailAddressManager
     * @param EntityManager       $entityManager
     * @param EmailCacheManager   $emailCacheManager
     * @param EngineInterface     $engineInterface
     * @param MailboxManager      $mailboxManager
     */
    public function __construct(
        EntityRoutingHelper $entityRoutingHelper,
        EmailAddressHelper $emailAddressHelper,
        EntityNameResolver $entityNameResolver,
        SecurityFacade $securityFacade,
        EmailAddressManager $emailAddressManager,
        EntityManager $entityManager,
        EmailCacheManager $emailCacheManager,
        EngineInterface $engineInterface,
        MailboxManager $mailboxManager
    ) {
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->emailAddressHelper  = $emailAddressHelper;
        $this->entityNameResolver  = $entityNameResolver;
        $this->securityFacade      = $securityFacade;
        $this->emailAddressManager = $emailAddressManager;
        $this->entityManager       = $entityManager;
        $this->emailCacheManager   = $emailCacheManager;
        $this->templating          = $engineInterface;
        $this->mailboxManager      = $mailboxManager;
    }

    /**
     * @param string      $emailAddress
     * @param string|null $ownerClass
     * @param mixed|null  $ownerId
     * @param bool        $excludeCurrentUser
     */
    public function preciseFullEmailAddress(
        &$emailAddress,
        $ownerClass = null,
        $ownerId = null,
        $excludeCurrentUser = false
    ) {
        if (!$this->emailAddressHelper->isFullEmailAddress($emailAddress)) {
            if (!empty($ownerClass) && !empty($ownerId)) {
                $owner = $this->entityRoutingHelper->getEntity($ownerClass, $ownerId);
                if ($owner) {
                    if ($this->doExcludeCurrentUser($excludeCurrentUser, $emailAddress, $owner)) {
                        return;
                    }
                    $ownerName = $this->entityNameResolver->getName($owner);
                    if (!empty($ownerName)) {
                        $emailAddress = $this->emailAddressHelper->buildFullEmailAddress($emailAddress, $ownerName);

                        return;
                    }
                }
            }
            $repo            = $this->emailAddressManager->getEmailAddressRepository($this->entityManager);
            $emailAddressObj = $repo->findOneBy(array('email' => $emailAddress));
            if ($emailAddressObj) {
                $owner = $emailAddressObj->getOwner();
                if ($owner) {
                    if ($this->doExcludeCurrentUser($excludeCurrentUser, $emailAddress, $owner)) {
                        return;
                    }
                    $ownerName = $this->entityNameResolver->getName($owner);
                    if (!empty($ownerName)) {
                        $emailAddress = $this->emailAddressHelper->buildFullEmailAddress($emailAddress, $ownerName);
                    }
                }
            }
        }
    }

    /**
     * @param bool $excludeCurrentUser
     * @param string $emailAddress
     * @param object $owner
     * @return bool
     */
    protected function doExcludeCurrentUser($excludeCurrentUser, &$emailAddress, $owner)
    {
        if (!$excludeCurrentUser) {
            return false;
        }
        $user = $this->getUser();
        if (ClassUtils::getClass($owner) === ClassUtils::getClass($user) && $owner->getId() === $user->getId()) {
            $emailAddress = false;

            return true;
        }

        return false;
    }

    /**
     * Get the current authenticated user
     *
     * @return User|UserInterface|EmailHolderInterface|EmailOwnerInterface|null
     */
    public function getUser()
    {
        return $this->securityFacade->getLoggedUser();
    }

    /**
     * Get current organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->securityFacade->getOrganization();
    }

    /**
     * @param EmailEntity $emailEntity
     */
    public function ensureEmailBodyCached(EmailEntity $emailEntity)
    {
        $this->emailCacheManager->ensureEmailBodyCached($emailEntity);
    }

    /**
     * @param string $className
     *
     * @return string
     */
    public function decodeClassName($className)
    {
        return $this->entityRoutingHelper->resolveEntityClass($className);
    }

    /**
     * @param User $user
     *
     * @return string
     */
    public function buildFullEmailAddress(User $user)
    {
        return $this->emailAddressHelper->buildFullEmailAddress(
            $user->getEmail(),
            $this->entityNameResolver->getName($user)
        );
    }

    /**
     * @param EmailEntity $emailEntity
     * @param             $templatePath
     *
     * @return null|string
     */
    public function getEmailBody(EmailEntity $emailEntity, $templatePath)
    {
        try {
            $this->emailCacheManager->ensureEmailBodyCached($emailEntity);
        } catch (LoadEmailBodyException $e) {
            return null;
        }

        return $this->templating
            ->render($templatePath, ['email' => $emailEntity]);
    }

    /**
     * @param $prefix
     * @param $subject
     *
     * @return string
     */
    public function prependWith($prefix, $subject)
    {
        if (!preg_match('/^' . $prefix . ':*/', $subject)) {
            return $prefix . $subject;
        }
        return $subject;
    }

    /**
     * @param string $entityClass
     * @param string $entityId
     * @return object
     */
    public function getTargetEntity($entityClass, $entityId)
    {
        return $this->entityRoutingHelper->getEntity($entityClass, $entityId);
    }

    /**
     * Returns mailboxes available to currently logged in user.
     *
     * @return Mailbox[]
     */
    public function getMailboxes()
    {
        $mailboxes = $this->mailboxManager->findAvailableMailboxes(
            $this->getUser(),
            $this->getOrganization()
        );

        return $mailboxes;
    }

    /**
     * @param $entity
     *
     * @return bool
     */
    protected function isFullQualifiedUser($entity)
    {
        return $entity instanceof UserInterface
        && $entity instanceof EmailHolderInterface
        && $entity instanceof EmailOwnerInterface;
    }
}
