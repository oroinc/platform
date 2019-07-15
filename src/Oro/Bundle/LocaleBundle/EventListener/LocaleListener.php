<?php

namespace Oro\Bundle\LocaleBundle\EventListener;

use Doctrine\DBAL\DBALException;
use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContextAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Set current localization to all depended services
 */
class LocaleListener implements EventSubscriberInterface
{
    /** @var LocaleSettings */
    private $localeSettings;

    /** @var LocalizationProviderInterface */
    private $currentLocalizationProvider;

    /** @var TranslatableListener */
    private $translatableListener;

    /** @var TranslatorInterface */
    private $translator;

    /** @var RequestContextAwareInterface|null */
    private $router;

    /** @var bool */
    private $installed;

    /** @var string */
    private $currentLanguage;

    /**
     * @param LocaleSettings $localeSettings
     * @param LocalizationProviderInterface $currentLocalizationProvider
     * @param TranslatableListener $translatableListener
     * @param TranslatorInterface $translator
     * @param RequestContextAwareInterface|null $router
     * @param string|bool|null $installed
     */
    public function __construct(
        LocaleSettings $localeSettings,
        LocalizationProviderInterface $currentLocalizationProvider,
        TranslatableListener $translatableListener,
        TranslatorInterface $translator,
        RequestContextAwareInterface $router,
        $installed
    ) {
        $this->localeSettings = $localeSettings;
        $this->currentLocalizationProvider = $currentLocalizationProvider;
        $this->translatableListener = $translatableListener;
        $this->translator = $translator;
        $this->router = $router;
        $this->installed = (bool) $installed;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request || !$this->installed) {
            return;
        }

        $language = $this->getCurrentLanguage();
        if (!$request->attributes->get('_locale')) {
            $request->setLocale($language);

            $this->router->getContext()->setParameter('_locale', $language);
        }

        $this->setPhpDefaultLocale($this->localeSettings->getLocale());

        $this->translatableListener->setTranslatableLocale($language);
        $this->translator->setLocale($language);
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
        if (!$this->installed) {
            return;
        }

        $isForced = $event->getInput()->hasParameterOption('--force');
        if ($isForced) {
            $this->installed = false;

            return;
        }

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

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            // must be registered after authentication
            KernelEvents::REQUEST => [['onKernelRequest', 7]],
            ConsoleEvents::COMMAND => [['onConsoleCommand']],
        ];
    }

    /**
     * @return string
     */
    private function getCurrentLanguage(): string
    {
        if (!$this->currentLanguage) {
            $localization = $this->currentLocalizationProvider->getCurrentLocalization();
            $this->currentLanguage = $localization
                ? $localization->getLanguageCode()
                : $this->localeSettings->getLanguage();
        }

        return $this->currentLanguage;
    }
}
