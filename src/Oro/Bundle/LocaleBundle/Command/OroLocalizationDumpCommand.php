<?php

declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Command;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Dumps locale settings for use in JavaScript.
 */
#[AsCommand(
    name: 'oro:localization:dump',
    description: 'Dumps locale settings for use in JavaScript.'
)]
class OroLocalizationDumpCommand extends Command
{
    private LocaleSettings $localeSettings;
    private Filesystem $filesystem;

    private string $projectDir;

    public function __construct(
        LocaleSettings $localeSettings,
        Filesystem $filesystem,
        string $projectDir,
        ?string $name = null
    ) {
        $this->localeSettings = $localeSettings;
        $this->filesystem = $filesystem;
        $this->projectDir = $projectDir;

        parent::__construct($name);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command dumps the locale settings used by JavaScript code
into the predefined public resource file.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $targetDir = realpath($this->projectDir . '/public') . '/js';

        $addressFormats = $this->getAddressFormats($this->localeSettings);
        $localeSettingsData = [
            'locale_data' => $this->localeSettings->getLocaleData(),
            'format' => [
                'address' => $addressFormats,
                'name' => $this->localeSettings->getNameFormats()
            ]
        ];

        $file = $targetDir . '/oro.locale_data.js';
        $output->writeln(
            sprintf(
                '<comment>%s</comment> <info>[file+]</info> %s',
                date('H:i:s'),
                basename($file)
            )
        );

        $content = 'define(' . json_encode($localeSettingsData) . ');';
        $this->filesystem->mkdir(dirname($file), 0777);
        if (false === @file_put_contents($file, $content)) {
            throw new \RuntimeException('Unable to write file ' . $file);
        }

        return Command::SUCCESS;
    }

    /**
     * Get address formats converted to simplified structure.
     */
    protected function getAddressFormats(LocaleSettings $localeSettings): array
    {
        $result = [];
        $formats = $localeSettings->getAddressFormats();
        foreach ($formats as $country => $formatData) {
            $result[$country] = $formatData[LocaleSettings::ADDRESS_FORMAT_KEY];
        }
        return $result;
    }
}
