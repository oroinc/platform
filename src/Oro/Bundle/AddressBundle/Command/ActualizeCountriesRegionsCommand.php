<?php

declare(strict_types=1);

namespace Oro\Bundle\AddressBundle\Command;

use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Actualize countries and region data from external provider
 */
#[AsCommand(
    name: 'oro:countries:regions:actualize',
    description: 'Actualize countries and regions data.',
)]
class ActualizeCountriesRegionsCommand extends Command
{
    private const BASE_URL = 'https://raw.githubusercontent.com/';
    private const BASE_URN = 'pycountry/pycountry/main/src/pycountry/databases/';

    private const COUNTRY_ISO_CODE = '3166-1';
    private const REGION_ISO_CODE = '3166-2';

    private const COUNTRY_FILE_NAME = '@OroAddressBundle/Migrations/Data/ORM/data/countries.yml';
    private const TRANSLATION_FILE_NAME = '@OroAddressBundle/Resources/translations/entities.en.yml';

    private const UPDATE_FULL_DATA_OPTION = 'update_full_data';
    private const ADD_NEW_DATA_OPTION = 'add_new_data';
    private const REMOVE_OLD_DATA_OPTION = 'remove_old_data';

    public function __construct(
        private FileLocatorInterface $fileLocator,
        private ExternalFileFactory $externalFileFactory,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument(
                'filepath',
                InputArgument::REQUIRED,
                'Path in application where we should save updated data file'
            )
            ->addOption(
                'update-full-data',
                null,
                InputOption::VALUE_NONE,
                'Update data fully, get from storage and add new, replace old codes, names'
            )
            ->addOption(
                'add-new-data',
                null,
                InputOption::VALUE_NONE,
                'Add only new countries and regions'
            )
            ->addOption(
                'remove-old-data',
                null,
                InputOption::VALUE_NONE,
                'Remove countries and regions from our storage'
            )
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command for updating countries and regions data 
from external provider.

  <info>php %command.full_name%</info>

The <info>filepath</info> argument must be used to set application path
where we should save updated data file (e.g var/data).

The <info>--update-full-data</info> option can be used to force updating.
Download external file and use it to create countries and regions data

The <info>--add-new-data</info> option can be used to add new countries or regions 
which absent in local file.

The <info>--remove-old-data</info> option can be used to remove countries and region 
which exist only in local file and absent in external file.

HELP
            )
            ->addUsage('filepath')
            ->addUsage('--update-full-data')
            ->addUsage('--add-new-data')
            ->addUsage('--remove-old-data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $options  = [
            self::UPDATE_FULL_DATA_OPTION => $input->getOption('update-full-data'),
            self::ADD_NEW_DATA_OPTION => $input->getOption('add-new-data'),
            self::REMOVE_OLD_DATA_OPTION => $input->getOption('remove-old-data'),
        ];

        $selectedOption = array_filter($options);

        if (count($selectedOption) !== 1) {
            $output->writeln(
                '<error> You must select one option: --update-full-data, --add-new-data or --remove-old-data </error>'
            );

            return Command::INVALID;
        }

        $countriesSplFileInfo = $this->externalFileFactory->createFromUrl(
            self::BASE_URL . self::BASE_URN . 'iso' . self::COUNTRY_ISO_CODE . '.json?raw=true'
        );

        $regionsSplFileInfo = $this->externalFileFactory->createFromUrl(
            self::BASE_URL . self::BASE_URN . 'iso' . self::REGION_ISO_CODE . '.json?raw=true'
        );

        $externalCountries = json_decode(file_get_contents($countriesSplFileInfo->getUrl()), true);
        $externalRegions = json_decode(file_get_contents($regionsSplFileInfo->getUrl()), true);

        try {
            $this->updateTranslations(
                $input->getArgument('filepath'),
                $externalCountries[self::COUNTRY_ISO_CODE],
                $externalRegions[self::REGION_ISO_CODE],
                array_key_first($selectedOption)
            );

            $this->updateCountriesRegionsData(
                $input->getArgument('filepath'),
                $externalCountries[self::COUNTRY_ISO_CODE],
                $externalRegions[self::REGION_ISO_CODE],
                array_key_first($selectedOption)
            );
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $output->writeln(
                '<error> ' . $exception->getMessage() . ' </error>'
            );

            return Command::INVALID;
        }

        return Command::SUCCESS;
    }

    private function updateTranslations(
        string $filePath,
        array $externalCountries,
        array $externalRegions,
        string $selectedOption
    ): void {
        try {
            $countriesRegionsTranslation = $this->getDataFromFile(
                self::TRANSLATION_FILE_NAME
            );
        } catch (\RuntimeException | \LogicException $exception) {
            $this->logger->error($exception->getMessage());
            throw new RuntimeException($exception->getMessage());
        }

        $countries = [];
        foreach ($externalCountries as $externalCountry) {
            $name = $externalCountry['common_name'] ?? $externalCountry['name'] ?? $externalCountry['official_name'];
            $countries[$externalCountry['alpha_2']] = $name;
        }

        $countriesRegionsTranslation['country'] = $this->updateTranslationsBySelectedOption(
            $countries,
            $countriesRegionsTranslation['country'],
            $selectedOption
        );

        $externalRegions = array_column($externalRegions, 'name', 'code');
        $countriesRegionsTranslation['region'] = $this->updateTranslationsBySelectedOption(
            $externalRegions,
            $countriesRegionsTranslation['region'],
            $selectedOption
        );
        try {
            $this->saveDataToFile(
                'entities.en.yml',
                $filePath,
                $countriesRegionsTranslation
            );
        } catch (\RuntimeException | \LogicException $exception) {
            throw new RuntimeException($exception->getMessage());
        }
    }

    private function updateTranslationsBySelectedOption(
        array $externalData,
        array $internalData,
        string $selectedOption
    ): array {
        $dataToRemove = $dataToAdd = [];

        [$dataToAdd, $dataToRemove] = match ($selectedOption) {
            self::UPDATE_FULL_DATA_OPTION => [
                array_diff_assoc($externalData, $internalData),
                array_diff_assoc($internalData, $externalData)
            ],
            self::ADD_NEW_DATA_OPTION => [array_diff_key($externalData, $internalData), $dataToRemove],
            self::REMOVE_OLD_DATA_OPTION => [$dataToAdd, array_diff_key($internalData, $externalData)],
        };

        $internalData = array_diff_key($internalData, $dataToRemove);
        $internalData = array_merge($internalData, $dataToAdd);
        ksort($internalData);

        return $internalData;
    }

    private function updateCountriesRegionsData(
        string $filePath,
        array $externalCountries,
        array $externalRegions,
        string $selectedOption
    ): void {
        try {
            $countriesRegionsData = $this->getDataFromFile(self::COUNTRY_FILE_NAME);
        } catch (\RuntimeException | \LogicException $exception) {
            $this->logger->error($exception->getMessage());
            throw new RuntimeException($exception->getMessage());
        }

        $externalData = $this->prepareCountriesExternalData($externalCountries, $externalRegions);

        $updatedCountriesRegionsData = match ($selectedOption) {
            self::UPDATE_FULL_DATA_OPTION => $externalData,
            self::ADD_NEW_DATA_OPTION => $this->prepareFileAddNewDataOption($externalData, $countriesRegionsData),
            self::REMOVE_OLD_DATA_OPTION => $this->prepareFileRemoveOldDataOption(
                $externalData,
                $countriesRegionsData
            ),
        };

        try {
            $this->saveDataToFile('countries.yml', $filePath, $updatedCountriesRegionsData);
        } catch (\RuntimeException | \LogicException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    private function prepareFileAddNewDataOption(array $externalData, array $internalData): array
    {
        foreach ($externalData as $countryCode => $countryData) {
            if (!isset($internalData[$countryCode])) {
                $internalData[$countryCode] = $countryData;
            } else {
                foreach ($countryData['regions'] as $regionCode => $regionData) {
                    if (!isset($internalData[$countryCode]['regions'][$regionCode])) {
                        $internalData[$countryCode]['regions'][$regionCode] = $regionData;
                    }
                }
            }
        }

        return $internalData;
    }

    private function prepareFileRemoveOldDataOption(array $externalData, array $internalData): array
    {
        foreach ($internalData as $countryCode => $data) {
            if (!isset($externalData[$countryCode])) {
                unset($internalData[$countryCode]);
            } else {
                foreach ($data['regions'] as $regionCode => $regionData) {
                    if (!isset($externalData[$countryCode]['regions'][$regionCode])) {
                        unset($internalData[$countryCode]['regions'][$regionCode]);
                    }
                }
            }
        }

        return $internalData;
    }

    private function prepareCountriesExternalData(array $externalCountries, array $externalRegions): array
    {
        $result = $regions = [];
        foreach ($externalRegions as $region) {
            [$countryCode, $code] = explode("-", $region['code']);
            $regions[$countryCode][$region['code'] ] = [
                    'combinedCode' => $region['code'],
                    'code' => $code,
            ];
        }

        array_multisort(
            array_column($externalCountries, 'alpha_2'),
            SORT_ASC,
            $externalCountries
        );

        foreach ($externalCountries as $country) {
            $result[$country['alpha_2']] = [
                'iso2Code' => $country['alpha_2'],
                'iso3Code' => $country['alpha_3'],
                'regions' => $regions[$country['alpha_2']] ?? [],
            ];
        }

        return $result;
    }

    private function getDataFromFile(string $fileName): array
    {
        $fileName = $this->fileLocator->locate($fileName);

        if (!$this->isFileAvailable($fileName)) {
            throw new \LogicException('File ' . $fileName . ' is not available');
        }

        $parsedData = Yaml::parse(file_get_contents($fileName));

        return $parsedData ?? [];
    }

    private function saveDataToFile(string $fileName, string $filePath, array $data): void
    {
        $filePath = realpath($filePath);
        if (!$filePath || !is_writable($filePath)) {
            throw new \LogicException('Folder ' . $filePath . ' is not exist or not writable');
        }

        $filePath .= DIRECTORY_SEPARATOR . $fileName;
        $yaml = Yaml::dump($data, Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE);

        if (!file_put_contents($filePath, $yaml)) {
            throw new \RuntimeException('Cannot write to file ' . $filePath);
        }
    }

    private function isFileAvailable(string $fileName): bool
    {
        return is_file($fileName) && is_readable($fileName);
    }
}
