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
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Set current localization to all depended services
 */
class LocaleListener implements EventSubscriberInterface
{
    private LocaleSettings $localeSettings;

    private LocalizationProviderInterface $currentLocalizationProvider;

    private TranslatableListener $translatableListener;

    private TranslatorInterface $translator;

    private ?RequestContextAwareInterface $router;

    private bool $installed;

    private ?string $currentLanguage = null;

    /**
     * @param LocaleSettings $localeSettings
     * @param LocalizationProviderInterface $currentLocalizationProvider
     * @param TranslatableListener $translatableListener
     * @param TranslatorInterface $translator
     * @param RequestContextAwareInterface|null $router
     * @param ApplicationState $applicationState
     */
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
        $this->installed = $applicationState->isInstalled();
    }

    public function onKernelRequest(RequestEvent $event): void
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
    public function setPhpDefaultLocale(string $locale)
    {
        \Locale::setDefault($locale);
    }

    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if (!$this->installed) {
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
