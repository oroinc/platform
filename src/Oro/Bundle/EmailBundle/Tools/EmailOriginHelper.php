<?php

namespace Oro\Bundle\EmailBundle\Tools;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailOriginHelper
{
    /** @var EmailModel */
    protected $emailModel;

    /** @var EntityManager */
    protected $em;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var  EmailOwnerProvider */
    protected $emailOwnerProvider;

    /** @var EmailAddressHelper */
    protected $emailAddressHelper;

    /** @var array */
    protected $origins = [];

    /**
     * @param DoctrineHelper         $doctrineHelper
     * @param TokenAccessorInterface $tokenAccessor
     * @param EmailOwnerProvider     $emailOwnerProvider
     * @param EmailAddressHelper     $emailAddressHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        TokenAccessorInterface $tokenAccessor,
        EmailOwnerProvider $emailOwnerProvider,
        EmailAddressHelper $emailAddressHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->tokenAccessor = $tokenAccessor;
        $this->emailOwnerProvider = $emailOwnerProvider;
        $this->emailAddressHelper = $emailAddressHelper;
    }

    /**
     * @param EmailModel $model
     */
    public function setEmailModel(EmailModel $model)
    {
        $this->emailModel = $model;
    }

    /**
     * @param mixed                 $emailOwner
     * @param OrganizationInterface $organization
     * @param string                $originName
     * @param bool                  $enableUseUserEmailOrigin
     *
     * @return mixed|null|object|InternalEmailOrigin|UserEmailOrigin
     */
    public function findEmailOrigin($emailOwner, $organization, $originName, $enableUseUserEmailOrigin)
    {
        if ($emailOwner instanceof User) {
            $origin = $this->getPreferredOrigin($emailOwner, $organization, $enableUseUserEmailOrigin);
        } elseif ($emailOwner instanceof Mailbox) {
            $origin = $emailOwner->getOrigin();
        } else {
            $origin = $this->getEntityManager()
                ->getRepository('OroEmailBundle:InternalEmailOrigin')
                ->findOneBy(['internalName' => $originName]);
        }

        return $origin;
    }

    /**
     * Find existing email origin entity by email string or create and persist new one.
     *
     * @param string                $email
     * @param OrganizationInterface $organization
     * @param string                $originName
     * @param boolean               $enableUseUserEmailOrigin
     * @param boolean               $secured Check origin can be used by current user
     *
     * @return EmailOrigin
     */
    public function getEmailOrigin(
        $email,
        OrganizationInterface $organization = null,
        $originName = InternalEmailOrigin::BAP,
        $enableUseUserEmailOrigin = true,
        $secured = false
    ) {
        $originKey = $originName . $email . $enableUseUserEmailOrigin;
        if (!$organization && $this->tokenAccessor->getOrganization()) {
            $organization = $this->tokenAccessor->getOrganization();
        }
        if (!array_key_exists($originKey, $this->origins)) {
            $emailOwners = $this->emailOwnerProvider
                ->findEmailOwners(
                    $this->getEntityManager(),
                    $this->emailAddressHelper->extractPureEmailAddress($email)
                );
            $origin = $this->findEmailOrigin(
                $this->chooseEmailOwner($emailOwners, $secured),
                $organization,
                $originName,
                $enableUseUserEmailOrigin
            );

            $this->origins[$originKey] = $origin;
        }

        return $this->origins[$originKey];
    }

    /**
     * Removes the helper state.
     */
    public function clear()
    {
        $this->origins = [];
    }

    /**
     * Get first accessible email owner
     *
     * @param EmailOwnerInterface[] $emailOwners
     * @param bool $secured
     *
     * @return null
     */
    protected function chooseEmailOwner($emailOwners, $secured)
    {
        $selectedEmailOwner = null;
        foreach ($emailOwners as $emailOwner) {
            if ($secured && !$this->hasOriginAccess($emailOwner)) {
                continue;
            }
            $selectedEmailOwner = $emailOwner;
            break;
        }

        return $selectedEmailOwner;
    }

    /**
     * Check on access to email owner data by logged in user
     *
     * @param EmailOwnerInterface $emailOwner
     *
     * @return bool
     */
    protected function hasOriginAccess($emailOwner)
    {
        $access = false;
        if ($emailOwner instanceof User
            && $this->tokenAccessor->getUserId() === $emailOwner->getId()) {
            $access = true;
        } elseif ($emailOwner instanceof Mailbox) {
            $ownerIds = [];
            $authorizedUsers = $emailOwner->getAuthorizedUsers();
            foreach ($authorizedUsers as $user) {
                $ownerIds[] = $user->getId();
            }

            $authorizedRoles = $emailOwner->getAuthorizedRoles();
            foreach ($authorizedRoles as $role) {
                $users = $this->getEntityManager()->getRepository('OroUserBundle:Role')
                    ->getUserQueryBuilder($role)
                    ->getQuery()->getResult();

                foreach ($users as $user) {
                    $ownerIds[] = $user->getId();
                }
            }

            if (in_array($this->tokenAccessor->getUserId(), $ownerIds, true)) {
                $access = true;
            }
        }

        return $access;
    }

    /**
     * @param mixed $origin
     *
     * @return bool
     */
    protected function isEmptyOrigin($origin)
    {
        return (null === $origin || ($origin instanceof Collection && $origin->isEmpty())) &&
            null !== $this->emailModel;
    }

    /**
     * Get imap origin if exists.
     *
     * @param User                  $user
     * @param OrganizationInterface $organization
     * @param bool                  $enableUseUserEmailOrigin
     *
     * @return InternalEmailOrigin|null|mixed
     */
    protected function getPreferredOrigin(User $user, $organization, $enableUseUserEmailOrigin)
    {
        $origins = new ArrayCollection();

        if ($enableUseUserEmailOrigin) {
            $origins = $user->getEmailOrigins()->filter(
                $this->getImapEnabledFilter($organization)
            );
        }
        if ($origins->isEmpty()) {
            $origins = $user->getEmailOrigins()->filter(
                $this->getInternalFilter($organization)
            );
        }
        $origin = $origins->isEmpty() ? null : $origins->first();

        if ($origin === null) {
            $origin = $this->createUserInternalOrigin($user, $organization);
        }

        return $origin;
    }

    /**
     * @param OrganizationInterface $organization
     *
     * @return \Closure
     */
    protected function getImapEnabledFilter(OrganizationInterface $organization = null)
    {
        return function ($item) use ($organization) {
            return $item instanceof UserEmailOrigin && $item->isActive() && $item->isSmtpConfigured()
                && (!$organization || $item->getOrganization() === $organization);
        };
    }

    /**
     * @param OrganizationInterface $organization
     *
     * @return \Closure
     */
    protected function getInternalFilter(OrganizationInterface $organization = null)
    {
        return function ($item) use ($organization) {
            return ($item->getOrganization() === $organization || !$organization) &&
                $item instanceof InternalEmailOrigin;
        };
    }

    /**
     * @param User                  $user
     * @param OrganizationInterface $organization
     *
     * @return InternalEmailOrigin
     */
    protected function createUserInternalOrigin(User $user, OrganizationInterface $organization = null)
    {
        $organization = $organization
            ? $organization
            : $user->getOrganization();

        $originName = InternalEmailOrigin::BAP . '_User_' . $user->getId();

        $outboxFolder = new EmailFolder();
        $outboxFolder
            ->setType(FolderType::SENT)
            ->setName(FolderType::SENT)
            ->setFullName(FolderType::SENT);

        $origin = new InternalEmailOrigin();
        $origin
            ->setName($originName)
            ->addFolder($outboxFolder)
            ->setOwner($user)
            ->setOrganization($organization);

        $user->addEmailOrigin($origin);

        $this->getEntityManager()->persist($origin);
        $this->getEntityManager()->persist($user);

        return $origin;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (null === $this->em) {
            $this->em = $this->doctrineHelper->getEntityManager('OroEmailBundle:Email');
        }

        return $this->em;
    }
}
