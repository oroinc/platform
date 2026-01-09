<?php

declare(strict_types=1);

namespace Oro\Bundle\AddressBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Update countries and region data in db from actualized countries.yml
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
#[AsCommand(
    name: 'oro:countries:regions:update-db',
    description: 'Update countries and regions data according to actualized countries.yml and entities.en.yml.',
)]
class UpdateCountriesRegionsCommand extends Command
{
    private const string DEFAULT_TRANSLATION_LOCALE = 'en';

    private const string ENTITY_DOMAIN = 'entities';

    private SymfonyStyle $symfonyStyle;

    private bool $force;

    private array $translationFromFile;

    private array $countriesRegionFromFile;


    public function __construct(private ManagerRegistry $doctrine, private FileLocatorInterface $fileLocator)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Update countries and regions data in database.'
            )
            ->addOption(
                'translation-file',
                null,
                InputOption::VALUE_OPTIONAL,
                'Translation file for updating countries and regions data in database.'
            )
            ->addOption(
                'countries-file',
                null,
                InputOption::VALUE_OPTIONAL,
                'Actualized countries and regions data for updating database.'
            )
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command for getting sql queries which will be implemented in case of force option set.

  <info>php %command.full_name%</info>

The <info>--force</info> option can be used to force updating.

    <info>php %command.full_name% --force</info>
HELP
            )
            ->addUsage('--force')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $translationFile = $input->getOption('translation-file') ?
            realpath($input->getOption('translation-file')) :
            '@OroAddressBundle/Resources/translations/entities.en.yml';


        $countriesRegionsData = $input->getOption('countries-file') ?
            realpath($input->getOption('countries-file')) :
            '@OroAddressBundle/Migrations/Data/ORM/data/countries.yml';

        $this->symfonyStyle = new SymfonyStyle($input, $output);
        $this->force = $input->getOption('force');
        $this->translationFromFile = $this->getDataFromFile($translationFile);
        $this->countriesRegionFromFile = $this->getDataFromFile($countriesRegionsData);

        $commandText = $this->force ? 'Command run in force mode, updating DB ...' :
            'To force execution run command with <info>--force</info> option:';

        $this->symfonyStyle->writeln($commandText);

        $this->loadCountriesAndRegions();

        return Command::SUCCESS;
    }

    private function loadCountriesAndRegions(): void
    {
        $countriesCode = array_keys($this->countriesRegionFromFile);

        $countriesToAdd = $this->getCountriesToAdd($countriesCode);
        $regionsToAdd = $this->getRegionsToAdd($this->countriesRegionFromFile);

        $countriesToDelete = $this->getCountriesToDelete($countriesCode);
        $regionsToDelete = $this->getRegionsToDelete($this->countriesRegionFromFile);

        $em = $this->doctrine->getManager();
        $em->getConnection()->beginTransaction();

        $translationData = $this->addTranslationKeys($em, $countriesToAdd, $regionsToAdd);

        $this->addNewCountries($this->countriesRegionFromFile, $countriesToAdd, $translationData);
        $this->addNewRegions($this->countriesRegionFromFile, $regionsToAdd, $translationData);

        $this->deleteOldCountries($countriesToDelete);
        $this->deleteOldRegions($regionsToDelete);

        $this->updateExistedCountryTranslation();
        $this->updateExistedRegionTranslation();

        if ($this->force) {
            $em->getConnection()->commit();
        } else {
            $em->getConnection()->rollback();
        }
    }

    private function getCountriesToDelete(array $countries): array
    {
        $em = $this->doctrine->getManagerForClass(Country::class);
        $connection = $em->getConnection();

        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('oro_dictionary_country', 'c')
            ->where($queryBuilder->expr()->eq('c.deleted', ':deleted'))
            ->andWhere($queryBuilder->expr()->notIn('c.iso2_code', ':countries'))
            ->setParameter('countries', $countries, Connection::PARAM_STR_ARRAY)
            ->setParameter('deleted', false, ParameterType::BOOLEAN);

        return $queryBuilder->execute()->fetchAllAssociative();
    }

    private function deleteOldCountries(array $countries): void
    {
        if (empty($countries)) {
            return;
        }

        $countriesCode = array_column($countries, 'iso2_code');

        $em = $this->doctrine->getManagerForClass(Country::class);
        $connection = $em->getConnection();
        $queryBuilder = $connection->createQueryBuilder();

        $queryBuilder->update('oro_dictionary_country')
            ->set('deleted', ':deleted')
            ->where($queryBuilder->expr()->in('iso2_code', ':countries'))
            ->setParameter('deleted', true)
            ->setParameter('countries', $countriesCode, Connection::PARAM_STR_ARRAY);

        $this->processQueryBuilder($queryBuilder);
    }

    private function getRegionsToDelete(array $countries): array
    {
        $regionsCode = [];
        foreach ($countries as $countryData) {
            if (empty($countryData['regions'])) {
                continue;
            }

            $regionsCode = array_merge($regionsCode, array_keys($countryData['regions']));
        }

        $em = $this->doctrine->getManagerForClass(Region::class);
        $connection = $em->getConnection();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('oro_dictionary_region', 'r')
            ->where($queryBuilder->expr()->eq('r.deleted', ':deleted'))
            ->andWhere($queryBuilder->expr()->notIn('r.combined_code', ':regions'))
            ->setParameter('regions', $regionsCode, Connection::PARAM_STR_ARRAY)
            ->setParameter('deleted', false, ParameterType::BOOLEAN);

        return $queryBuilder->execute()->fetchAllAssociative();
    }

    private function deleteOldRegions(array $regions): void
    {
        if (empty($regions)) {
            return;
        }

        $regionsCode = array_column($regions, 'combined_code');

        $em = $this->doctrine->getManagerForClass(Country::class);
        $connection = $em->getConnection();
        $queryBuilder = $connection->createQueryBuilder();

        $queryBuilder->update('oro_dictionary_region')
            ->set('deleted', ':deleted')
            ->where($queryBuilder->expr()->in('combined_code', ':regions'))
            ->setParameter('deleted', true)
            ->setParameter('regions', $regionsCode, Connection::PARAM_STR_ARRAY);

        $this->processQueryBuilder($queryBuilder);
    }

    private function getCountriesToAdd(array $countries): array
    {
        $internalCountries = $this->doctrine->getRepository(Country::class)->findBy(['deleted' => false]);

        $internalCountriesFormatted = array_map(
            fn ($internalCountry) => $internalCountry->getIso2Code(),
            $internalCountries
        );

        return array_diff($countries, $internalCountriesFormatted);
    }

    private function addNewCountries(array $countries, array $newCountriesCode, array $translationData): void
    {
        if (empty($newCountriesCode)) {
            return;
        }

        $em = $this->doctrine->getManagerForClass(Country::class);
        $connection = $em->getConnection();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->insert('oro_dictionary_country')
            ->values(['iso2_code' => ':iso2Code', 'iso3_code' => ':iso3Code', 'name' => ':name']);

        foreach ($newCountriesCode as $newCountryCode) {
            ['iso2Code' => $iso2Code, 'iso3Code' => $iso3Code, 'regions' => $regions] = $countries[$newCountryCode];
            $key = sprintf('country.%s', $iso2Code);
            $name = $translationData[$key] ?? $key;

            $queryBuilder->setParameter('iso2Code', $iso2Code)
                ->setParameter('iso3Code', $iso3Code)
                ->setParameter('name', $name);

            $this->processQueryBuilder($queryBuilder);
        }
    }

    private function getRegionsToAdd(array $countries): array
    {
        $regionsCode = [];
        foreach ($countries as $countryData) {
            if (!empty($countryData['regions'])) {
                $regionsCode = array_merge($regionsCode, array_keys($countryData['regions']));
            }
        }

        $internalRegions = $this->doctrine->getRepository(Region::class)->findAll();
        $internalRegionsFormatted = array_map(
            fn ($internalRegion) => $internalRegion->getCombinedCode(),
            $internalRegions
        );

        return array_diff($regionsCode, $internalRegionsFormatted);
    }

    private function addNewRegions(array $countries, array $newRegionsCode, array $translationData): void
    {
        if (empty($newRegionsCode)) {
            return;
        }

        $em = $this->doctrine->getManagerForClass(Region::class);
        $connection = $em->getConnection();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->insert('oro_dictionary_region')
            ->values([
                'combined_code' => ':combinedCode',
                'country_code' => ':countryCode',
                'code' => ':code',
                'name' => ':name'
            ]);

        foreach ($newRegionsCode as $newRegionCode) {
            $countryCode = explode('-', $newRegionCode);
            $countryCode = $countryCode[0];
            $newRegion = $countries[$countryCode]['regions'][$newRegionCode];
            $key = sprintf('region.%s', $newRegion['combinedCode']);
            $name = $translationData[$key] ?? $key;

            $queryBuilder->setParameter('combinedCode', $newRegion['combinedCode'])
                ->setParameter('countryCode', $countryCode)
                ->setParameter('code', $newRegion['code'])
                ->setParameter('name', $name);

            $this->processQueryBuilder($queryBuilder);
        }
    }

    private function getNativeSqlFromQueryBuilder(QueryBuilder $queryBuilder): string
    {
        $connection = $this->doctrine->getManager()->getConnection();
        $sql = $queryBuilder->getSQL();
        $params = $queryBuilder->getParameters();

        foreach ($params as $name => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_map(fn ($v) => $connection->quote($v), $value));
            } elseif (is_string($value)) {
                $value = $connection->quote($value);
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_int($value)) {
                $value = "'" . $value . "'";
            }

            $sql = preg_replace("/:$name/", $value, $sql, 1);
        }

        return $sql . ';';
    }

    private function addTranslationKeys(EntityManager $em, array $countries, array $regions): array
    {
        $translationKeyData = [];
        $connection = $em->getConnection();
        $updatedTranslation = [];
        $queryBuilder = $connection->createQueryBuilder();

        $values = [
            'key' => ':key',
            'domain' => ':domain'
        ];

        $queryBuilder->insert('oro_translation_key')
            ->values($values);

        foreach ($countries as $country) {
            $key = sprintf('country.%s', $country);
            $queryBuilder->setParameter('key', $key)
                ->setParameter('domain', self::ENTITY_DOMAIN);

            $insertedId = $this->executeQueryBuilderWithInsertedId($connection, $queryBuilder);

            if (!$this->force) {
                $valuesWithId = array_merge(['id' => $insertedId], $values);
                $queryBuilder->values($valuesWithId)
                    ->setParameter('id', $insertedId);
                $this->symfonyStyle->writeln($this->getNativeSqlFromQueryBuilder($queryBuilder));
            }

            if (isset($this->translationFromFile['country'][$country])) {
                $translationKeyData[$insertedId] = $this->translationFromFile['country'][$country];
                $updatedTranslation[$key] = $this->translationFromFile['country'][$country];
            }
        }

        foreach ($regions as $region) {
            $key = sprintf('region.%s', $region);
            $queryBuilder->values($values)
                ->setParameter('key', $key)
                ->setParameter('domain', self::ENTITY_DOMAIN);

            $insertedId = $this->executeQueryBuilderWithInsertedId($connection, $queryBuilder);

            if (!$this->force) {
                $valuesWithId = array_merge(['id' => $insertedId], $values);
                $queryBuilder->values($valuesWithId)
                    ->setParameter('id', $insertedId);
                $this->symfonyStyle->writeln($this->getNativeSqlFromQueryBuilder($queryBuilder));
            }

            if (isset($this->translationFromFile['region'][$region])) {
                $translationKeyData[$insertedId] = $this->translationFromFile['region'][$region];
                $updatedTranslation[$key] = $this->translationFromFile['region'][$region];
            }
        }

        $this->addTranslationText($em, $translationKeyData);

        return $updatedTranslation;
    }

    private function addTranslationText(EntityManager $em, array $translationData): void
    {
        $connection = $em->getConnection();
        $queryBuilder = $connection->createQueryBuilder();

        $queryBuilder->select('*')
            ->from('oro_language', 'l')
            ->where('l.code = :code')
            ->setParameter('code', self::DEFAULT_TRANSLATION_LOCALE);

        $languageId = $queryBuilder->execute()->fetchOne();

        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->insert('oro_translation')
            ->values([
                'translation_key_id' => ':key',
                'language_id' => ':language',
                'value' => ':value',
                'scope' => ':scope'
            ]);

        foreach ($translationData as $translationKeyId => $translationValue) {
            $queryBuilder->setParameter('key', $translationKeyId)
                ->setParameter('language', $languageId)
                ->setParameter('value', $translationValue)
                ->setParameter('scope', Translation::SCOPE_SYSTEM);

            $this->processQueryBuilder($queryBuilder);
        }
    }

    private function updateExistedCountryTranslation(): void
    {
        $translationData = $this->translationFromFile['country'];

        $em = $this->doctrine->getManagerForClass(Country::class);
        $connection = $em->getConnection();

        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('oro_dictionary_country', 'c')
            ->where($queryBuilder->expr()->eq('c.deleted', ':deleted'))
            ->setParameter('deleted', false, ParameterType::BOOLEAN);

        $internalCountries = $queryBuilder->execute()->fetchAllAssociative();

        foreach ($internalCountries as $country) {
            $countryCode = $country['iso2_code'];
            if (isset($translationData[$countryCode]) &&
                $country['name'] !== $translationData[$countryCode]
            ) {
                $newName = $translationData[$countryCode];
                $qb = $connection->createQueryBuilder();
                $qb->update('oro_dictionary_country')
                    ->set('name', ':name')
                    ->where('iso2_code = :code')
                    ->setParameter('name', $newName)
                    ->setParameter('code', $countryCode);

                $this->processQueryBuilder($qb);

                $translationQueryBuilder = $connection->createQueryBuilder();
                $expr = $translationQueryBuilder->expr();

                $translationQueryBuilder->update('oro_translation')
                    ->set('value', ':value')
                    ->setParameter('value', $newName)
                    ->setParameter('key', sprintf('country.%s', $countryCode))
                    ->setParameter('languageCode', self::DEFAULT_TRANSLATION_LOCALE);

                $translationKeySubQuerySql = $this->getTranslationKeySql($connection);
                $translationQueryBuilder->where(
                    $expr->eq(
                        'translation_key_id',
                        '(' . $translationKeySubQuerySql . ')'
                    )
                );
                $languageSubQuerySql = $this->getLanguageSql($connection);
                $translationQueryBuilder->andWhere(
                    $expr->eq(
                        'language_id',
                        '(' . $languageSubQuerySql . ')'
                    )
                );

                $this->processQueryBuilder($translationQueryBuilder);
            }
        }
    }

    private function updateExistedRegionTranslation(): void
    {
        $translationData = $this->translationFromFile['region'];

        $em = $this->doctrine->getManagerForClass(Region::class);
        $connection = $em->getConnection();

        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('oro_dictionary_region', 'r')
            ->where($queryBuilder->expr()->eq('r.deleted', ':deleted'))
            ->setParameter('deleted', false, ParameterType::BOOLEAN);

        $internalRegions = $queryBuilder->execute()->fetchAllAssociative();
        foreach ($internalRegions as $region) {
            $regionCode = $region['combined_code'];
            if (isset($translationData[$regionCode]) &&
                $region['name'] !== $translationData[$regionCode]
            ) {
                $newName = $translationData[$regionCode];
                $qb = $connection->createQueryBuilder();
                $qb->update('oro_dictionary_region')
                    ->set('name', ':name')
                    ->where('combined_code = :code')
                    ->setParameter('name', $newName)
                    ->setParameter('code', $regionCode);

                $this->processQueryBuilder($qb);

                $translationQueryBuilder = $connection->createQueryBuilder();
                $expr = $translationQueryBuilder->expr();

                $translationQueryBuilder->update('oro_translation')
                    ->set('value', ':value')
                    ->setParameter('value', $newName)
                    ->setParameter('key', sprintf('region.%s', $regionCode))
                    ->setParameter('languageCode', self::DEFAULT_TRANSLATION_LOCALE);

                $translationKeySubQuerySql = $this->getTranslationKeySql($connection);
                $translationQueryBuilder->where(
                    $expr->eq(
                        'translation_key_id',
                        '(' . $translationKeySubQuerySql . ')'
                    )
                );

                $languageSubQuerySql = $this->getLanguageSql($connection);
                $translationQueryBuilder->andWhere(
                    $expr->eq(
                        'language_id',
                        '(' . $languageSubQuerySql . ')'
                    )
                );

                $this->processQueryBuilder($translationQueryBuilder);
            }
        }
    }

    private function getTranslationKeySql(Connection $connection): string
    {
        $translationKeySubQueryBuilder = $connection->createQueryBuilder();
        $translationKeySubQueryBuilder->select('tk.id')
            ->from('oro_translation_key', 'tk')
            ->where($translationKeySubQueryBuilder->expr()->eq('tk.key', ':key'));

        return $translationKeySubQueryBuilder->getSQL();
    }

    private function getLanguageSql(Connection $connection): string
    {
        $languageSubQueryBuilder = $connection->createQueryBuilder();
        $languageSubQueryBuilder->select('l.id')
            ->from('oro_language', 'l')
            ->where($languageSubQueryBuilder->expr()->eq('l.code', ':languageCode'));

        return $languageSubQueryBuilder->getSQL();
    }

    private function processQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->execute();
        if (!$this->force) {
            $this->symfonyStyle->writeln($this->getNativeSqlFromQueryBuilder($queryBuilder));
        }
    }

    private function isFileAvailable(string $fileName): bool
    {
        return is_file($fileName) && is_readable($fileName);
    }

    private function getDataFromFile(string $fileName): array
    {
        $fileName = $this->fileLocator->locate($fileName);

        if (!$this->isFileAvailable($fileName)) {
            throw new \LogicException('File ' . $fileName . 'is not available');
        }

        $fileName = realpath($fileName);

        return Yaml::parse(file_get_contents($fileName));
    }

    private function executeQueryBuilderWithInsertedId(Connection $connection, QueryBuilder $queryBuilder): int
    {
        $sql = $queryBuilder->getSQL() . ' RETURNING id';

        return (int) $connection->executeQuery($sql, $queryBuilder->getParameters())->fetchOne();
    }
}
