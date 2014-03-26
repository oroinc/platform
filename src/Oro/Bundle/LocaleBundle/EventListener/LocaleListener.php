<?php

namespace Oro\Bundle\LocaleBundle\EventListener;

use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
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
     * @var TranslatableListener
     */
    protected $translatableListener;

    /**
     * @var bool
     */
    protected $isInstalled;

    /**
     * @param LocaleSettings       $localeSettings
     * @param TranslatableListener $translatableListener
     * @param bool|null|string     $installed
     */
    public function __construct(
        LocaleSettings $localeSettings,
        TranslatableListener $translatableListener,
        $installed
    ) {
        $this->localeSettings       = $localeSettings;
        $this->translatableListener = $translatableListener;
        $this->isInstalled          = !empty($installed);
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
                $request->setLocale($this->localeSettings->getLanguage());
            }
            $this->setPhpDefaultLocale($this->localeSettings->getLocale());

            $this->translatableListener->setTranslatableLocale(
                $this->localeSettings->getLanguage()
            );
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
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $isForced = $event->getInput()->hasParameterOption('--force');
        if ($isForced) {
            $this->isInstalled = false;

            return;
        }

        if ($this->isInstalled) {
            $this->setPhpDefaultLocale(
                $this->localeSettings->getLocale()
            );

            $this->translatableListener->setTranslatableLocale(
                $this->localeSettings->getLanguage()
            );
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            // must be registered after Symfony's original LocaleListener
            KernelEvents::REQUEST  => array(array('onKernelRequest', 15)),
            ConsoleEvents::COMMAND => array(array('onConsoleCommand')),
        );
    }
}
