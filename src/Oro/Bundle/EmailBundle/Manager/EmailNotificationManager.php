<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Oro\Bundle\EmailBundle\Entity\Provider\EmailProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Class EmailNotificationManager
 * @package Oro\Bundle\EmailBundle\Manager
 */
class EmailNotificationManager
{
    /** @var EmailProvider */
    protected $emailProvider;
    /** @var HtmlTagHelper */
    protected $htmlTagHelper;
    /** @var Router */
    protected $router;
    /** @var EmailCacheManager */
    protected $emailCacheManager;

    public function __construct(
        EmailProvider $emailProvider,
        HtmlTagHelper $htmlTagHelper,
        Router $router,
        EmailCacheManager $emailCacheManager
    ) {
        $this->emailProvider = $emailProvider;
        $this->htmlTagHelper = $htmlTagHelper;
        $this->router = $router;
        $this->emailCacheManager = $emailCacheManager;
    }

    public function getEmails(User $user, $maxEmailsDisplay)
    {
        $emails = $this->emailProvider->getNewEmails($user, $maxEmailsDisplay);

        $emailsData = [];
        /** @var $email Email */
        foreach ($emails as $email) {
            $isSeen = $email['seen'];
            $email = $email[0];
            $bodyContent = '';
            try {
                $this->emailCacheManager->ensureEmailBodyCached($email);
                $bodyContent = $this->htmlTagHelper->shorten(
                    $this->htmlTagHelper->stripTags($this->htmlTagHelper->purify($email->getEmailBody()->getBodyContent()))
                );
            } catch (LoadEmailBodyException $e) {
                // no content
            }
            $emailsData[] = [
                'route'=> $this->router->generate('oro_email_email_reply', ['id' => $email->getId()]),
                'id' => $email->getId(),
                'seen' => $isSeen,
                'subject' => $email->getSubject(),
                'bodyContent' => $bodyContent,
                'fromName' => $email->getFromName()
            ];
        }

        return $emailsData;
    }

    public function getCountNewEmails(User $user)
    {
        return $this->emailProvider->getCountNewEmails($user);
    }
}