<?php

namespace Oro\Bundle\LocaleBundle\EventListener;

use Doctrine\DBAL\DBALException;
use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContextAwareInterface;
use Symfony\Component\Translation\TranslatorInterface;

class LocaleListener implements EventSubscriberInterface
{
    /** @var LocaleSettings */
    private $localeSettings = false;

    /** @var TranslatableListener */
    private $translatableListener = false;

    /** @var bool */
    private $isInstalled = null;

    /** @var RequestContextAwareInterface */
    private $router = false;

    /** @var TranslatorInterface */
    private $translator = false;

    /** @var Localization */
    private $currentLocalization = false;

    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        if (!$request) {
            return;
        }

        if ($this->getIsInstalled()) {
            $localization = $this->getCurrentLocalization();
            if (null !== $localization) {
                $language = $localization->getLanguageCode();
            } else {
                $language = $this->getLocaleSettings()->getLanguage();
            }
            $locale = $this->getLocaleSettings()->getLocale();

            if (!$request->attributes->get('_locale')) {
                $request->setLocale($language);
                if (null !== $this->getRouter()) {
                    $this->getRouter()->getContext()->setParameter('_locale', $language);
                }
            }
            $this->setPhpDefaultLocale($locale);

            $this->getTranslatableListener()->setTranslatableLocale($language);
            $this->getTranslator()->setLocale($language);
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

        if ($this->getIsInstalled()) {
            try {
                $locale   = $this->getLocaleSettings()->getLocale();
                $language = $this->getLocaleSettings()->getLanguage();
            } catch (DBALException $exception) {
                // application is not installed
                return;
            }

            $this->setPhpDefaultLocale($locale);
            $this->getTranslatableListener()->setTranslatableLocale($language);
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            // must be registered after authentication
            KernelEvents::REQUEST  => [['onKernelRequest', 7]],
            ConsoleEvents::COMMAND => [['onConsoleCommand']],
        ];
    }

    /**
     * @return LocaleSettings
     */
    protected function getLocaleSettings()
    {
        if ($this->localeSettings === false) {
            $this->localeSettings = $this->container->get('oro_locale.settings');
        }

        return $this->localeSettings;
    }

    /**
     * @return TranslatableListener
     */
    protected function getTranslatableListener()
    {
        if ($this->translatableListener === false) {
            $this->translatableListener = $this->container->get('stof_doctrine_extensions.listener.translatable');
        }

        return $this->translatableListener;
    }

    /**
     * @return bool
     */
    protected function getIsInstalled()
    {
        if ($this->isInstalled === null) {
            $this->isInstalled = $this->container->getParameter('installed');
        }

        return $this->isInstalled;
    }

    /**
     * @return RequestContextAwareInterface
     */
    protected function getRouter()
    {
        if ($this->router === false) {
            $this->router = $this->container->get('router', ContainerInterface::NULL_ON_INVALID_REFERENCE);
        }

        return $this->router;
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        if ($this->translator === false) {
            $this->translator = $this->container->get('translator');
        }

        return $this->translator;
    }

    /**
     * @return Localization|null
     */
    protected function getCurrentLocalization()
    {
        if ($this->currentLocalization === false) {
            /** @var $provider CurrentLocalizationProvider */
            $provider = $this->container->get('oro_locale.provider.current_localization');
            $this->currentLocalization = $provider->getCurrentLocalization();
        }

        return $this->currentLocalization;
    }
}
