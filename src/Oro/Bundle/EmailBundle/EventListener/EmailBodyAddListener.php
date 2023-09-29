<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The event listener class the act after adding email body that updating activity description
 * and adding attachment to the Email target entities
 */
class EmailBodyAddListener
{
    private const LINK_ATTACHMENT_CONFIG_OPTION = 'auto_link_attachments';

    private ConfigProvider $configProvider;
    private EmailAttachmentManager $attachmentManager;
    private EmailActivityListProvider $activityListProvider;
    private AuthorizationCheckerInterface $authorizationChecker;
    private TokenStorageInterface $tokenStorage;
    private ActivityListChainProvider $chainProvider;
    private ManagerRegistry $doctrine;

    public function __construct(
        EmailAttachmentManager $attachmentManager,
        ConfigProvider $configProvider,
        EmailActivityListProvider $activityListProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        ActivityListChainProvider $chainProvider,
        ManagerRegistry $doctrine
    ) {
        $this->attachmentManager = $attachmentManager;
        $this->configProvider = $configProvider;
        $this->activityListProvider = $activityListProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->chainProvider = $chainProvider;
        $this->doctrine = $doctrine;
    }

    public function linkToScope(EmailBodyAdded $event): void
    {
        if (null !== $this->tokenStorage->getToken()
            && !$this->authorizationChecker->isGranted(
                'CREATE',
                ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, Attachment::class)
            )
        ) {
            return;
        }

        $email = $event->getEmail();
        $entities = $this->activityListProvider->getTargetEntities($email);
        foreach ($entities as $entity) {
            $entityConfig = $this->configProvider->getConfig(ClassUtils::getClass($entity));
            if ($entityConfig->get(self::LINK_ATTACHMENT_CONFIG_OPTION)) {
                foreach ($email->getEmailBody()->getAttachments() as $attachment) {
                    $this->attachmentManager->linkEmailAttachmentToTargetEntity($attachment, $entity);
                }
            }
        }
    }

    public function updateActivityDescription(EmailBodyAdded $event): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Email::class);
        $em->beginTransaction();
        try {
            $activityList = $this->chainProvider->getUpdatedActivityList($event->getEmail(), $em);
            if ($activityList) {
                $em->persist($activityList);
                $em->flush();
            }
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }
}
