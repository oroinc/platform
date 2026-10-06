<?php

namespace Oro\Bundle\LocaleBundle\EventListener;

use Doctrine\DBAL\Exception;
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
    private ?LocaleSwitcher $localeSwitcher;
    private ?bool $installed = null;
    private ?string $currentLanguage = null;

    public function __construct(
        LocaleSettings $localeSettings,
        LocalizationProviderInterface $currentLocalizationProvider,
        TranslatableListener $translatableListener,
        TranslatorInterface $translator,
        RequestContextAwareInterface $router,
        ApplicationState $applicationState,
        ?LocaleSwitcher $localeSwitcher = null
    ) {
        $this->localeSettings = $localeSettings;
        $this->currentLocalizationProvider = $currentLocalizationProvider;
        $this->translatableListener = $translatableListener;
        $this->translator = $translator;
        $this->router = $router;
        $this->applicationState = $applicationState;
        $this->localeSwitcher = $localeSwitcher;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->isInstalled()) {
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
            // LocaleSwitcher::setLocale() overrides the router context parameter,
            // but a route-provided locale must stay authoritative for URL generation.
            $this->router->getContext()->setParameter('_locale', $routeLocale);
        }
        // after switchLocale(): LocaleSwitcher sets the PHP default locale to the language code,
        // while the formatting locale (e.g. de_DE) must win.
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
        } catch (Exception $exception) {
            // application is not installed
            return;
        }

        $this->switchLocale($language);
        $this->setPhpDefaultLocale($locale);
        $this->translatableListener->setTranslatableLocale($language);
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered after authentication
            KernelEvents::REQUEST => [['onKernelRequest', 7]],
            ConsoleEvents::COMMAND => [['onConsoleCommand']],
        ];
    }

    /**
     * Switches the language through the LocaleSwitcher so that the switcher's own locale is updated
     * as well: since symfony/http-kernel 7.4.17 (and 6.4.x counterpart) LocaleAwareListener restores
     * every kernel.locale_aware service to its own stored locale after a sub-request, and a switcher
     * left at the default locale would cascade that default back to the translator.
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
