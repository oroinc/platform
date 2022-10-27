<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Download\TranslationDownloader;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Helper\FileBasedLanguageHelper;
use Symfony\Component\Console\Command\Command;
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
    /** @var string */
    protected static $defaultName = 'oro:translation:update';

    private TranslationDownloader $translationDownloader;
    private ManagerRegistry $doctrine;
    private FileBasedLanguageHelper $fileBasedLanguageHelper;

    public function __construct(
        TranslationDownloader $translationDownloader,
        ManagerRegistry $doctrine,
        FileBasedLanguageHelper $fileBasedLanguageHelper
    ) {
        $this->translationDownloader = $translationDownloader;
        $this->doctrine = $doctrine;
        $this->fileBasedLanguageHelper = $fileBasedLanguageHelper;
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

        $langCode = $input->getArgument('language');

        if ($input->getOption('all')) {
            if ($langCode) {
                $io->error(sprintf(
                    'The --all option and the language argument ("%s") cannot be used together.',
                    $langCode
                ));

                return 1;
            }

            return $this->updateAllLanguages($io);
        }

        if ($langCode) {
            /** @var Language|null $language */
            $language = $this->findLanguage($langCode);
            if (null === $language) {
                $io->error(sprintf(
                    'Language "%s" is not installed.'
                    . ' Translations can be updated only for an already installed language.',
                    $langCode
                ));

                return 1;
            }

            return $this->updateLanguage($language, $io) ? 0 : 1;
        }

        $this->printInstalledLanguageInfo($io);

        return 0;
    }

    private function updateAllLanguages(SymfonyStyle $io): int
    {
        $exitCode = 0;
        foreach ($this->getLanguages() as $language) {
            if (!$this->updateLanguage($language, $io, true)) {
                $exitCode = 1;
            }
        }

        return $exitCode;
    }

    private function updateLanguage(
        Language $language,
        SymfonyStyle $io,
        bool $onlyWarningOnMissingTranslations = false
    ): bool {
        $result = true;

        $langName = $this->getLanguageName($language);
        $langCode = $language->getCode();

        if ($this->fileBasedLanguageHelper->isFileBasedLocale($langCode)) {
            $io->text(sprintf('Language "%s" is file based.', $langCode));

            return $result;
        }

        $io->section(sprintf('%s (%s):', $langName, $langCode));
        $io->text('Checking availability...');
        $metrics = $this->translationDownloader->fetchLanguageMetrics($langCode);
        if (null === $metrics) {
            $message = sprintf('No "%s" (%s) translations are available for download.', $langName, $langCode);
            if ($onlyWarningOnMissingTranslations) {
                $io->text($message);
            } else {
                $io->error($message);
                $result = false;
            }
        } else {
            $io->text('Downloading translations...');
            $pathToSave = $this->translationDownloader->getTmpDir('download_') . DIRECTORY_SEPARATOR . $langCode;
            $this->translationDownloader->downloadTranslationsArchive($langCode, $pathToSave);

            $io->text('Applying translations...');
            $this->translationDownloader->loadTranslationsFromArchive($pathToSave, $langCode);
            $language->setInstalledBuildDate($metrics['lastBuildDate']);

            /** @var EntityManager $em */
            $em = $this->doctrine->getManagerForClass(Language::class);
            $em->flush($language);

            $io->success(sprintf('Update completed for "%s" language.', $langName));
        }

        return $result;
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

    private function getLanguageName(Language $language): string
    {
        if (Locales::exists($language->getCode())) {
            return Locales::getName($language->getCode(), 'en');
        }

        return $language->getCode();
    }

    private function findLanguage(string $langCode): ?Language
    {
        return $this->getLanguageRepository()->findOneBy(['code' => $langCode]);
    }

    /**
     * @return Language[]
     */
    private function getLanguages(): array
    {
        return $this->getLanguageRepository()->findAll();
    }

    private function getLanguageRepository(): LanguageRepository
    {
        return $this->doctrine->getRepository(Language::class);
    }
}
