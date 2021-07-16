<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Download\TranslationDownloader;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Component\Log\LogAndThrowExceptionTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Intl\Locales;

/**
 * Downloads and updates translations.
 */
class OroTranslationUpdateCommand extends Command
{
    use LogAndThrowExceptionTrait;

    /** @var string */
    protected static $defaultName = 'oro:translation:update';

    private TranslationDownloader $translationDownloader;
    private ManagerRegistry $doctrine;
    private ?LanguageRepository $languageRepository = null;
    private ?LoggerInterface $logger;

    public function __construct(
        TranslationDownloader $translationDownloader,
        ManagerRegistry $doctrine,
        ?LoggerInterface $logger
    ) {
        $this->translationDownloader = $translationDownloader;
        $this->doctrine = $doctrine;
        /** @noinspection UnusedConstructorDependenciesInspection used by a trait */
        $this->logger = $logger;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure(): void
    {
        $this
            ->addArgument('language', InputArgument::OPTIONAL, 'Language code')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Update all installed languages')
            ->setDescription('Downloads and updates translations.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command downloads and installs a new version of translations for a specified language:

  <info>php %command.full_name% <language></info>

The <info>--all</info> option can be used to download and update translations for all installed languages:

  <info>php %command.full_name% --all</info>

The command will print the list of all languages installed in the application if run without any options:

  <info>php %command.full_name%</info>

HELP
            )
            ->addUsage('--all')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            if ($input->getOption('all') && !empty($input->getArgument('language'))) {
                $this->throwErrorException(
                    RuntimeException::class,
                    'The --all option and the language argument ("{language}") cannot be used together.',
                    ['language' => $input->getArgument('language')]
                );
            }

            if ($input->getOption('all')) {
                foreach ($this->getLanguages() as $language) {
                    $this->updateLanguage($language, $io, true);
                }
                return 0;
            }

            if (!empty($input->getArgument('language'))) {
                /** @var Language $language */
                $language = $this->getRepository()->findOneBy(['code' => $input->getArgument('language')]);
                if (!$language) {
                    $this->throwErrorException(
                        RuntimeException::class,
                        'Language "{language}" is not installed.'
                        . ' Translations can be updated only for an already installed language.',
                        ['language' => $input->getArgument('language')]
                    );
                }
                $this->updateLanguage($language, $io);
                return 0;
            }

            $this->printInstalledLanguageInfo($io);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return $e->getCode() ?: 1;
        }

        return 0;
    }

    /**
     * @throws \RuntimeException if there are no available translations for the specified language and
     *                           $onlyWarningOnMissingTranslations is false.
     * @throws \Doctrine\ORM\ORMException if failed to update installedBuildDate field on the language.
     */
    private function updateLanguage(
        Language $language,
        SymfonyStyle $io,
        bool $onlyWarningOnMissingTranslations = false
    ): void {
        $langName = $this->getLanguageName($language);
        $langCode = $language->getCode();

        $io->section(\sprintf('%s (%s):', $langName, $langCode));
        $io->text('Checking availability...');
        $metrics = $this->translationDownloader->fetchLanguageMetrics($langCode);
        if (null === $metrics) {
            if ($onlyWarningOnMissingTranslations) {
                $io->text(\sprintf('No "%s" (%s) translations are available for download.', $langName, $langCode));
                return;
            }
            $this->throwErrorException(
                RuntimeException::class,
                'No "{language_name}" ({language_code}) translations are available for download.',
                ['language_name' => $langName, 'language_code' => $langCode]
            );
        }

        $io->text('Downloading translations...');
        $pathToSave = $this->translationDownloader->getTmpDir('download_') . DIRECTORY_SEPARATOR . $langCode;
        $this->translationDownloader->downloadTranslationsArchive($langCode, $pathToSave);

        $io->text('Applying translations...');
        $this->translationDownloader->loadTranslationsFromArchive($pathToSave, $langCode);

        $language->setInstalledBuildDate($metrics['lastBuildDate']);

        /** @var EntityManager $em */
        $em = $this->doctrine->getManagerForClass(Language::class);
        $em->flush($language);

        $io->success(\sprintf('Update completed for "%s" language.', $langName));
    }

    private function printInstalledLanguageInfo(SymfonyStyle $io): void
    {
        $io->section('Installed Languages:');

        $headers = [
            'ID',
            'Code',
            'Name',
            'Enabled',
            'Installed',
            'Available Translations',
        ];

        $rows = [];
        foreach ($this->getLanguages() as $lang) {
            $metrics = $this->translationDownloader->fetchLanguageMetrics($lang->getCode());
            $rows[] = [
                $lang->getId(),
                $lang->getCode(),
                $this->getLanguageName($lang),
                $lang->isEnabled() ? 'Yes' : 'No',
                $lang->getInstalledBuildDate() ? $lang->getInstalledBuildDate()->format('Y-m-d H:i:sA') : 'N/A',
                $metrics ? 'Available' : 'N/A',
            ];
        }

        $io->table($headers, $rows);
    }

    private function getRepository(): LanguageRepository
    {
        if (!$this->languageRepository) {
            $this->languageRepository = $this->doctrine->getRepository(Language::class);
        }

        return $this->languageRepository;
    }

    private function getLanguageName(Language $language): string
    {
        return Locales::getName($language->getCode(), 'en') ?? $language->getCode();
    }

    /**
     * @return Language[]
     */
    private function getLanguages(): array
    {
        return $this->getRepository()->findAll();
    }
}
