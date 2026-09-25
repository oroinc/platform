<?php

namespace Oro\Bundle\LocaleBundle\EventListener;

use Doctrine\DBAL\DBALException;
use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContextAwareInterface;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Sets current localization to all depended services.
 */
class LocaleListener implements EventSubscriberInterface
{
    private LocaleSettings $localeSettings;
    private LocalizationProviderInterface $currentLocalizationProvider;
    private TranslatableListener $translatableListener;
    private TranslatorInterface $translator;
    private RequestContextAwareInterface $router;
    private ApplicationState $applicationState;
    private ?LocaleSwitcher $localeSwitcher = null;
    private ?bool $installed = null;
    private ?string $currentLanguage = null;

    public function __construct(
        LocaleSettings $localeSettings,
        LocalizationProviderInterface $currentLocalizationProvider,
        TranslatableListener $translatableListener,
        TranslatorInterface $translator,
        RequestContextAwareInterface $router,
        ApplicationState $applicationState
    ) {
        $this->localeSettings = $localeSettings;
        $this->currentLocalizationProvider = $currentLocalizationProvider;
        $this->translatableListener = $translatableListener;
        $this->translator = $translator;
        $this->router = $router;
        $this->applicationState = $applicationState;
    }

    public function setLocaleSwitcher(?LocaleSwitcher $localeSwitcher): void
    {
        $this->localeSwitcher = $localeSwitcher;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request || !$this->isInstalled()) {
            return;
        }

        $language = $this->getCurrentLanguage();
        $routeLocale = $request->attributes->get('_locale');
        if (!$routeLocale) {
            $request->setLocale($language);

            $this->router->getContext()->setParameter('_locale', $language);
        }

        $this->switchLocale($language);
        if ($routeLocale) {
            // LocaleSwitcher::setLocale() changes the "_locale" parameter in the router context.
            // The route gives the locale for the URL generation. Set this locale again.
            $this->router->getContext()->setParameter('_locale', $routeLocale);
        }

        // LocaleSwitcher sets the PHP default locale to the language code, for example "de".
        // The application must use the format locale, for example "de_DE". Set this locale again.
        $this->setPhpDefaultLocale($this->localeSettings->getLocale());

        $this->translatableListener->setTranslatableLocale($language);
    }

    public function setPhpDefaultLocale(string $locale): void
    {
        \Locale::setDefault($locale);
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        if (!$this->isInstalled()) {
            return;
        }

        /**
         * Skip setting of localization settings during initialization of extended entities.
         * This is required to prevent loading of {@see \Oro\Bundle\LocaleBundle\Entity\Localization} entity;
         * this is an extendable entity and loading of it causes incorrect initialization ORM metadata for it.
         * Steps to reproduce the issue:
         * * remove the cache directory
         * * run "cache:clear" command
         * * run "doctrine:schema:update --dump-sql" command
         * * this command must not return "ALTER TABLE oro_localization DROP serialized_data;" SQL query
         */
        if (CommandExecutor::isCurrentCommand('oro:entity-extend:cache:', true)) {
            return;
        }

        // Set localization for the consumer in the extension
        // @Oro\Bundle\MessageQueueBundle\Consumption\Extension\LocaleExtension
        if (CommandExecutor::isCurrentCommand('oro:message-queue:consume')) {
            return;
        }

        $isForced = $event->getInput()->hasParameterOption('--force');
        if ($isForced) {
            $this->installed = false;

            return;
        }

        try {
            $locale = (string)$this->localeSettings->getLocale();
            $language = $this->localeSettings->getLanguage();
        } catch (DBALException $exception) {
            // application is not installed
            return;
        }

        $this->setPhpDefaultLocale($locale);
        $this->translatableListener->setTranslatableLocale($language);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered after authentication
            KernelEvents::REQUEST => [['onKernelRequest', 7]],
            ConsoleEvents::COMMAND => [['onConsoleCommand']],
        ];
    }

    /**
     * Sets the language through the LocaleSwitcher, so the switcher gets the locale too.
     *
     * Since symfony/http-kernel 6.4.44, LocaleAwareListener restores every kernel.locale_aware
     * service to its own stored locale after a sub-request. A switcher left at the default locale
     * would push that default back to the translator.
     */
    private function switchLocale(string $language): void
    {
        if (null !== $this->localeSwitcher) {
            $this->localeSwitcher->setLocale($language);
        } elseif ($this->translator instanceof LocaleAwareInterface) {
            $this->translator->setLocale($language);
        }
    }

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

    private function isInstalled(): bool
    {
        if (null === $this->installed) {
            $this->installed = $this->applicationState->isInstalled();
        }

        return $this->installed;
    }
}
