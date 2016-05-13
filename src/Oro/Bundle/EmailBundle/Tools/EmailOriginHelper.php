<?php

namespace Oro\Bundle\EmailBundle\Tools;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Class EmailOriginHelper
 *
 * @package Oro\Bundle\EmailBundle\Tools
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailOriginHelper
{
    /** @var EmailModel */
    protected $emailModel;

    /** @var EntityManager */
    protected $em;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
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

        if ($this->isEmptyOrigin($origin)) {
            $user   = $this->emailModel->getCampaignOwner();
            $origin = $this->getPreferredOrigin($user, $organization, $enableUseUserEmailOrigin);
        }

        return $origin;
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
