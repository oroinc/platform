<?php

namespace Oro\Bundle\LocaleBundle\Command;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Console command to dump locale settings to the JS file for using on frontend.
 */
class OroLocalizationDumpCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:localization:dump';

    /** @var LocaleSettings */
    private $localeSettings;

    /** @var Filesystem */
    private $filesystem;

    /** @var string */
    private $projectDir;

    /**
     * @param LocaleSettings $localeSettings
     * @param Filesystem $filesystem
     * @param string $projectDir
     * @param string|null $name
     */
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Dumps oro js-localization');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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
    }

    /**
     * Get address formats converted to simplified structure.
     *
     * @param LocaleSettings $localeSettings
     * @return array
     */
    protected function getAddressFormats(LocaleSettings $localeSettings)
    {
        $result = [];
        $formats = $localeSettings->getAddressFormats();
        foreach ($formats as $country => $formatData) {
            $result[$country] = $formatData[LocaleSettings::ADDRESS_FORMAT_KEY];
        }
        return $result;
    }
}
