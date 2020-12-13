<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Command;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Exception\TranslationProviderException;
use Oro\Bundle\TranslationBundle\Provider\ExternalTranslationsProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Intl\Locales;

/**
 * Downloads and updates translations.
 */
class OroLanguageUpdateCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:language:update';

    private ExternalTranslationsProvider $externalTranslationsProvider;
    private DoctrineHelper $doctrineHelper;
    private ?LanguageRepository $languageRepository = null;

    public function __construct(
        ExternalTranslationsProvider $externalTranslationsProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->externalTranslationsProvider = $externalTranslationsProvider;
        $this->doctrineHelper = $doctrineHelper;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption('language', null, InputOption::VALUE_OPTIONAL, 'Language code')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Apply to all installed languages')
            ->setDescription('Downloads and updates translations.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command downloads and installs a new version of translations for a specified language:

  <info>php %command.full_name% --language=<language></info>

The <info>--all</info> option can be used to download and update all installed languages:

  <info>php %command.full_name% --all</info>

The command will print the list of all languages added to the application if run without any options:

  <info>php %command.full_name%</info>

HELP
            )
            ->addUsage('--language=<language>')
            ->addUsage('--all')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('language') && !$input->getOption('all')) {
            $this->outputList($output);
        } else {
            if ($input->getOption('all')) {
                foreach ($this->getLanguages() as $language) {
                    $this->updateLanguage($language, $output);
                }

                return;
            }

            /** @var Language $language */
            $language = $this->getRepository()->findOneBy(['code' => $input->getOption('language')]);
            if (!$language) {
                $output->writeln(sprintf('<error>Language "%s" not found</error>.', $input->getOption('language')));

                return;
            }
            $this->updateLanguage($language, $output);
        }
    }

    private function updateLanguage(Language $language, OutputInterface $output): void
    {
        try {
            $output->writeln(sprintf('Processing language "%s" ...', $this->getLanguageName($language)));
            $langName = $this->getLanguageName($language);
            if ($this->externalTranslationsProvider->updateTranslations($language)) {
                $output->writeln(sprintf('Installation completed for "%s" language.', $langName));
            } else {
                $output->writeln(sprintf('No available translations found for "%s".', $langName));
            }
        } catch (TranslationProviderException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
        }
    }

    private function outputList(OutputInterface $output): void
    {
        $table = new Table($output);
        $table->setHeaders([
            'ID',
            'Code',
            'Name',
            'Enabled',
            'Installed',
            'CrowdIN Status',
        ])->setRows([]);

        /** @var Language $language */
        foreach ($this->getLanguages() as $language) {
            $table->addRow([
                $language->getId(),
                $language->getCode(),
                $this->getLanguageName($language),
                $language->isEnabled() ? 'Yes' : 'No',
                $language->getInstalledBuildDate() ? $language->getInstalledBuildDate()->format('Y-m-d H:i:sA') : 'N/A',
                $this->externalTranslationsProvider->hasTranslations($language) ? 'Avail. Translations' : 'N/A',
            ]);
        }
        $table->render();
    }


    private function getRepository(): LanguageRepository
    {
        if (!$this->languageRepository) {
            /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
            $this->languageRepository = $this->doctrineHelper->getEntityRepositoryForClass(Language::class);
        }

        return $this->languageRepository;
    }

    private function getLanguageName(Language $language): string
    {
        $code = $language->getCode();
        $name = Languages::getName($code, 'en');
        if ($name) {
            return $name;
        }

        $name = Locales::getName($code, 'en');

        return $name ?: $code;
    }

    /**
     * @return Language[]
     */
    private function getLanguages()
    {
        return $this->getRepository()->findAll();
    }
}
