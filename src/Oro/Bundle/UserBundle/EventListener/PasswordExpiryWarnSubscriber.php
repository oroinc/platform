<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\UserBundle\Entity\User;

class PasswordExpiryWarnSubscriber implements EventSubscriberInterface
{
    public static $periodMarkers = [1, 3, 7];

    /** @var Session */
    protected $session;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param Session $session
     */
    public function __construct(Session $session, TranslatorInterface $translator)
    {
        $this->session    = $session;
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin'];
    }

    /**
     * @param  InteractiveLoginEvent $event
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();

        if (!$user instanceof User) {
            return;
        }

        if (null === $passwordExpiryDate = $user->getPasswordExpiresAt()) {
            return;
        }

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        if ($passwordExpiryDate < $now) {
            return;
        }

        foreach (self::$periodMarkers as $days) {
            $interval = new \DateInterval('P'. $days .'D');
            $date = $now->add($interval)->format('Y-m-d');

            if ($date === $passwordExpiryDate->format('Y-m-d')) {
                $message = $this->translator->transChoice(
                    'oro.user.password.expiration.message',
                    $days,
                    ['%count%' => $days]
                );
                $this->session->getFlashBag()->add('warning', $message);

                break;
            }

            $now->sub($interval);
        }
    }
}
