<?php

namespace Oro\Bundle\NotificationBundle\EventListener;

use Gedmo\Translatable\TranslatableListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LocaleListener implements EventSubscriberInterface
{
    /**
     * @var bool
     */
    protected $isInstalled;
    /**
     * @var TranslatableListener
     */
    private $translatableListener;

    /**
     * @param TranslatableListener $translatableListener
     * @param string|bool|null     $installed
     */
    public function __construct(TranslatableListener $translatableListener, $installed)
    {
        $this->isInstalled = !empty($installed);
        $this->translatableListener = $translatableListener;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        if (!$request || !$this->isInstalled) {
            return;
        }

        $this->translatableListener->setDefaultLocale($request->getLocale());
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        /** @var Request $request */
        $request = $event->getRequest();
        $this->setRequest($request);
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            // must be registered after Symfony's original LocaleListener
            KernelEvents::REQUEST => [['onKernelRequest', 15]],
        ];
    }
}
