<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Oro\Bundle\EmailBundle\Entity\Provider\EmailProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

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
    /** @var ConfigManager */
    protected $configManager;

    public function __construct(
        EmailProvider $emailProvider,
        HtmlTagHelper $htmlTagHelper,
        Router $router,
        EmailCacheManager $emailCacheManager,
        ConfigManager $configManager
    ) {
        $this->emailProvider = $emailProvider;
        $this->htmlTagHelper = $htmlTagHelper;
        $this->router = $router;
        $this->emailCacheManager = $emailCacheManager;
        $this->configManager = $configManager;
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
                    $this->htmlTagHelper->stripTags(
                        $this->htmlTagHelper->purify($email->getEmailBody()->getBodyContent())
                    )
                );
            } catch (LoadEmailBodyException $e) {
                // no content
            }
            $emailsData[] = [
                'route' => $this->router->generate('oro_email_email_reply', ['id' => $email->getId()]),
                'id' => $email->getId(),
                'seen' => $isSeen,
                'subject' => $email->getSubject(),
                'bodyContent' => $bodyContent,
                'fromName' => $email->getFromName(),
                'linkFromName' => $this->getFromNameLink($email)
            ];
        }

        return $emailsData;
    }

    protected function getFromNameLink(Email $email)
    {
        $path = false;
        if ($email->getFromEmailAddress() && $email->getFromEmailAddress()->getOwner()) {
            $className = $email->getFromEmailAddress()->getOwner()->getClass();
            $routeName = $this->configManager->getEntityMetadata($className)->getRoute('view', false);
            $path = $this->router->generate($routeName, ['id' => $email->getFromEmailAddress()->getOwner()->getId()]);
        }

        return $path;
    }

    public function getCountNewEmails(User $user)
    {
        return $this->emailProvider->getCountNewEmails($user);
    }
}
