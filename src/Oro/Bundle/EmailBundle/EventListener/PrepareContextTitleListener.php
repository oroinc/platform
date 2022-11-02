<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityBundle\Event\PrepareContextTitleEvent;
use Oro\Bundle\EmailBundle\Entity\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Updates a title and an URL for Email entity.
 */
class PrepareContextTitleListener
{
    private UrlGeneratorInterface $urlGenerator;
    private ManagerRegistry $doctrine;

    public function __construct(UrlGeneratorInterface $urlGenerator, ManagerRegistry $doctrine)
    {
        $this->urlGenerator = $urlGenerator;
        $this->doctrine = $doctrine;
    }

    public function prepareContextTitle(PrepareContextTitleEvent $event): void
    {
        if ($event->getTargetClass() !== Email::class) {
            return;
        }

        $item = $event->getItem();
        $emailId = $item['targetId'];
        /** @var Email $email */
        $email = $this->doctrine->getManagerForClass(Email::class)
            ->find(Email::class, $emailId);
        $item['title'] = $email->getSubject();
        $item['link'] = $this->urlGenerator->generate(
            'oro_email_thread_view',
            ['id' => $emailId],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $event->setItem($item);
    }
}
