<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Class EmailNotificationManager
 * @package Oro\Bundle\EmailBundle\Manager
 */
class EmailNotificationManager
{
    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /** @var Router */
    protected $router;

    /** @var EmailCacheManager */
    protected $emailCacheManager;

    /** @var ConfigManager */
    protected $configManager;

    /** @var EntityManager */
    protected $em;

    /**
     * @param EntityManager $entityManager
     * @param HtmlTagHelper $htmlTagHelper
     * @param Router $router
     * @param EmailCacheManager $emailCacheManager
     * @param ConfigManager $configManager
     */
    public function __construct(
        EntityManager $entityManager,
        HtmlTagHelper $htmlTagHelper,
        Router $router,
        EmailCacheManager $emailCacheManager,
        ConfigManager $configManager
    ) {
        $this->em = $entityManager;
        $this->htmlTagHelper = $htmlTagHelper;
        $this->router = $router;
        $this->emailCacheManager = $emailCacheManager;
        $this->configManager = $configManager;
    }

    /**
     * @param User         $user
     * @param Organization $organization
     * @param int          $maxEmailsDisplay
     * @param int|null     $folderId
     * @param bool         $cacheBody
     *
     * @return array
     */
    public function getEmails(User $user, Organization $organization, $maxEmailsDisplay, $folderId, $cacheBody = true)
    {
        $emails = $this->em->getRepository('OroEmailBundle:Email')->getNewEmails(
            $user,
            $organization,
            $maxEmailsDisplay,
            $folderId
        );

        $emailsData = [];
        /** @var $email Email */
        foreach ($emails as $element) {
            $isSeen = $element['seen'];
            $email = $element[0];
            $bodyContent = '';
            try {
                if ($cacheBody) {
                    $this->emailCacheManager->ensureEmailBodyCached($email);
                }
                $emailBody = $email->getEmailBody();
                if ($emailBody) {
                    $bodyContent = $this->htmlTagHelper->shorten(
                        $this->htmlTagHelper->stripTags(
                            $this->htmlTagHelper->purify($emailBody->getBodyContent())
                        )
                    );
                }
            } catch (LoadEmailBodyException $e) {
                // no content
            }

            $emailId = $email->getId();
            $emailsData[] = [
                'replyRoute' => $this->router->generate('oro_email_email_reply', ['id' => $emailId]),
                'replyAllRoute' => $this->router->generate('oro_email_email_reply_all', ['id' => $emailId]),
                'forwardRoute' => $this->router->generate('oro_email_email_forward', ['id' => $emailId]),
                'id' => $emailId,
                'seen' => $isSeen,
                'subject' => $email->getSubject(),
                'bodyContent' => $bodyContent,
                'fromName' => $email->getFromName(),
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
        return $this->em->getRepository('OroEmailBundle:Email')->getCountNewEmails($user, $organization, $folderId);
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
            $path = null;
            try {
                $path = $this->router->generate(
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
