<?php

namespace Oro\Bundle\LocaleBundle\EventListener;

use Gedmo\Translatable\TranslatableListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class LocaleListener implements EventSubscriberInterface
{
    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @var bool
     */
    protected $isInstalled;
    /**
     * @var TranslatableListener
     */
    private $translatableListener;

    /**
     * @param LocaleSettings       $localeSettings
     * @param TranslatableListener $translatableListener
     * @param string|bool|null     $installed
     */
    public function __construct(LocaleSettings $localeSettings, TranslatableListener $translatableListener, $installed)
    {
        $this->localeSettings = $localeSettings;
        $this->isInstalled = !empty($installed);

        $this->translatableListener = $translatableListener;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        if (!$request) {
            return;
        }

        if ($this->isInstalled) {
            if (!$request->attributes->get('_locale')) {
                $request->setLocale($this->localeSettings->getLocale());

                $this->translatableListener->setDefaultLocale($this->localeSettings->getLocale());
            }
            $this->setPhpDefaultLocale($this->localeSettings->getLocale());
        }
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $this->setRequest($request);
    }

    /**
     * @param string $locale
     */
    public function setPhpDefaultLocale($locale)
    {
        \Locale::setDefault($locale);
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            // must be registered after Symfony's original LocaleListener
            KernelEvents::REQUEST => array(array('onKernelRequest', 15)),
        );
    }
}
