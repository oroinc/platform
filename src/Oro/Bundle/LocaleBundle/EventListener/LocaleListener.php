<?php

namespace Oro\Bundle\LocaleBundle\EventListener;

use Doctrine\DBAL\DBALException;

use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RequestContextAwareInterface;

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
     * @var RequestContextAwareInterface
     */
    protected $router;

    /**
     * @param LocaleSettings               $localeSettings
     * @param TranslatableListener         $translatableListener
     * @param bool|null|string             $installed
     * @param RequestContextAwareInterface $router
     */
    public function __construct(
        LocaleSettings $localeSettings,
        TranslatableListener $translatableListener,
        $installed,
        RequestContextAwareInterface $router = null
    ) {
        $this->localeSettings       = $localeSettings;
        $this->translatableListener = $translatableListener;
        $this->isInstalled          = !empty($installed);
        $this->router               = $router;
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
                if (null !== $this->router) {
                    $this->router->getContext()->setParameter('_locale', $this->localeSettings->getLanguage());
                }
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
            try {
                $locale = $this->localeSettings->getLocale();
                $language = $this->localeSettings->getLanguage();
            } catch (DBALException $exception) {
                // application is not installed
                return;
            }

            $this->setPhpDefaultLocale($locale);
            $this->translatableListener->setTranslatableLocale($language);
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            // must be registered before Symfony's original LocaleListener
            KernelEvents::REQUEST  => array(array('onKernelRequest', 17)),
            ConsoleEvents::COMMAND => array(array('onConsoleCommand')),
        );
    }
}
