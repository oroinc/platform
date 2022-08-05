<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides information about new emails.
 */
class EmailNotificationManager
{
    private ManagerRegistry $doctrine;
    private HtmlTagHelper $htmlTagHelper;
    private UrlGeneratorInterface $urlGenerator;
    private ConfigManager $configManager;
    private AclHelper $aclHelper;

    public function __construct(
        ManagerRegistry $doctrine,
        HtmlTagHelper $htmlTagHelper,
        UrlGeneratorInterface $urlGenerator,
        ConfigManager $configManager,
        AclHelper $aclHelper
    ) {
        $this->doctrine = $doctrine;
        $this->htmlTagHelper = $htmlTagHelper;
        $this->urlGenerator = $urlGenerator;
        $this->configManager = $configManager;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param User         $user
     * @param Organization $organization
     * @param int          $maxEmailsDisplay
     * @param int|null     $folderId
     *
     * @return array
     */
    public function getEmails(User $user, Organization $organization, $maxEmailsDisplay, $folderId)
    {
        $emails = $this->doctrine->getRepository(Email::class)
            ->getNewEmails($user, $organization, $maxEmailsDisplay, $folderId, $this->aclHelper);

        $emailsData = [];
        /** @var Email $email */
        foreach ($emails as $emailUser) {
            $email = $emailUser->getEmail();
            $bodyContent = '';
            $emailBody = $email->getEmailBody();
            if ($emailBody) {
                $bodyContent = $emailBody->getTextBody();
            }

            $emailId = $email->getId();
            $emailsData[] = [
                'replyRoute' => $this->urlGenerator->generate('oro_email_email_reply', ['id' => $emailId]),
                'replyAllRoute' => $this->urlGenerator->generate('oro_email_email_reply_all', ['id' => $emailId]),
                'forwardRoute' => $this->urlGenerator->generate('oro_email_email_forward', ['id' => $emailId]),
                'id' => $emailId,
                'seen' => $emailUser->isSeen(),
                'subject' => $this->htmlTagHelper->purify($email->getSubject()),
                'bodyContent' => $this->htmlTagHelper->purify($bodyContent),
                'fromName' => $this->htmlTagHelper->purify($email->getFromName()),
                'linkFromName' => $this->getFromNameLink($email)
            ];
        }

        return $emailsData;
    }

    /**
     * Get count new emails
     *
     * @param User $user
     * @param Organization  $organization
     * @param int|null      $folderId
     *
     * @return integer
     */
    public function getCountNewEmails(User $user, Organization $organization, $folderId = null)
    {
        return $this->doctrine->getRepository(Email::class)
            ->getCountNewEmails($user, $organization, $folderId, $this->aclHelper);
    }

    private function getFromNameLink(Email $email): ?string
    {
        if (!$email->getFromEmailAddress()) {
            return null;
        }

        $owner = $email->getFromEmailAddress()->getOwner();
        if (null === $owner) {
            return null;
        }

        $routeName = $this->configManager->getEntityMetadata(ClassUtils::getClass($owner))->getRoute('view', false);
        try {
            return $this->urlGenerator->generate($routeName, ['id' => $owner->getId()]);
        } catch (RouteNotFoundException $e) {
            return null;
        }
    }
}
