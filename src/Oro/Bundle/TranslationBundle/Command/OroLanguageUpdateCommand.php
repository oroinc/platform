<?php

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
 * Installs/Updates language's external translations
 */
class OroLanguageUpdateCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:language:update';

    /** @var ExternalTranslationsProvider */
    private $externalTranslationsProvider;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var LanguageRepository */
    private $languageRepository;

    /**
     * @param ExternalTranslationsProvider $externalTranslationsProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ExternalTranslationsProvider $externalTranslationsProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->externalTranslationsProvider = $externalTranslationsProvider;
        $this->doctrineHelper = $doctrineHelper;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Installs/Updates language\'s external translations')
            ->addOption(
                'language',
                null,
                InputOption::VALUE_OPTIONAL,
                'Language code to install/update translations'
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Install/Update translations for all installed languages'
            );
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * @param Language $language
     * @param OutputInterface $output
     */
    private function updateLanguage(Language $language, OutputInterface $output)
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

    /**
     * @param OutputInterface $output
     *
     * @return int
     */
    private function outputList(OutputInterface $output)
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


    /**
     * @return LanguageRepository
     */
    private function getRepository()
    {
        if (!$this->languageRepository) {
            $this->languageRepository = $this->doctrineHelper->getEntityRepositoryForClass(Language::class);
        }

        return $this->languageRepository;
    }

    /**
     * @param Language $language
     *
     * @return string
     */
    private function getLanguageName(Language $language)
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
