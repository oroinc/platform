<?php

namespace Oro\Bundle\EmailBundle\Manager;

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
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /** @var UrlGeneratorInterface */
    protected $urlGenerator;

    /** @var ConfigManager */
    protected $configManager;

    /** @var AclHelper */
    private $aclHelper;

    public function __construct(
        ManagerRegistry $doctrine,
        HtmlTagHelper $htmlTagHelper,
        UrlGeneratorInterface $urlGenerator,
        ConfigManager $configManager
    ) {
        $this->doctrine = $doctrine;
        $this->htmlTagHelper = $htmlTagHelper;
        $this->urlGenerator = $urlGenerator;
        $this->configManager = $configManager;
    }

    public function setAclHelper(AclHelper $aclHelper)
    {
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
        $emails = $this->doctrine->getManagerForClass(Email::class)
            ->getRepository(Email::class)
            ->getNewEmailsWithAcl($user, $organization, $maxEmailsDisplay, $folderId, $this->aclHelper);

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
        return $this->doctrine->getManagerForClass(Email::class)
            ->getRepository(Email::class)
            ->getCountNewEmailsWithAcl($user, $organization, $folderId, $this->aclHelper);
    }

    /**
     * @param Email $email
     *
     * @return bool|string
     */
    protected function getFromNameLink(Email $email)
    {
        $path = false;
        if ($email->getFromEmailAddress() && $email->getFromEmailAddress()->getOwner()) {
            $className = $email->getFromEmailAddress()->getOwner()->getClass();
            $routeName = $this->configManager->getEntityMetadata($className)->getRoute('view', false);
            try {
                $path = $this->urlGenerator->generate(
                    $routeName,
                    ['id' => $email->getFromEmailAddress()->getOwner()->getId()]
                );
            } catch (RouteNotFoundException $e) {
                return false;
            }
        }

        return $path;
    }
}
