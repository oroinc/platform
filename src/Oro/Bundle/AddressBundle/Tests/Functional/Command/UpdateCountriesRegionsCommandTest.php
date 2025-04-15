<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Command;

use Doctrine\DBAL\Connection;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountriesAndRegionsDataForUpdate;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Command\CommandTestingTrait;

/**
 * @dbIsolationPerTest
 */
class UpdateCountriesRegionsCommandTest extends WebTestCase
{
    use CommandTestingTrait;

    private Connection $connection;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadCountriesAndRegionsDataForUpdate::class
        ]);

        $this->connection = self::getContainer()->get('doctrine')->getConnection();
    }

    public function testExecuteWithoutForce()
    {
        $expectedInsertRegionQuery = "INSERT INTO oro_dictionary_region (combined_code, country_code, code, name) "
            . "VALUES('%s', '%s', '%s', '%s');";

        $commandTester = $this->doExecuteCommand(
            'oro:countries:regions:update-db',
            [
                '--translation-file' => __DIR__ . '/data/entities.en.yml',
                '--countries-file' => __DIR__ . '/data/countries.yml',
            ]
        );

        self::assertOutputContains(
            $commandTester,
            sprintf(
                "INSERT INTO oro_dictionary_country (iso2_code, iso3_code, name) VALUES('%s', '%s', '%s');",
                'AA',
                'AAA',
                'New A'
            )
        );

        self::assertOutputContains(
            $commandTester,
            sprintf(
                "UPDATE oro_dictionary_country SET name = '%s' WHERE iso2_code = '%s';",
                'QQLand',
                'QQ',
            )
        );

        self::assertOutputContains(
            $commandTester,
            sprintf(
                $expectedInsertRegionQuery,
                'YY-01',
                'YY',
                '01',
                'region.YY-01'
            )
        );

        self::assertOutputContains(
            $commandTester,
            sprintf(
                $expectedInsertRegionQuery,
                'AA-AA',
                'AA',
                'AA',
                'region.AA-AA'
            )
        );
    }

    public function testExecuteWithForce()
    {
        $doctrine = self::getContainer()->get('doctrine');

        $countryRepository = $doctrine->getManager()->getRepository(Country::class);
        $regionRepository = $doctrine->getManager()->getRepository(Region::class);

        $expectedInsertRegionQuery = "INSERT INTO oro_dictionary_region (combined_code, country_code, code, name) "
            . "VALUES('%s', '%s', '%s', '%s');";

        $commandTester = $this->doExecuteCommand(
            'oro:countries:regions:update-db',
            [
                '--translation-file' => __DIR__ . '/data/entities.en.yml',
                '--countries-file' => __DIR__ . '/data/countries.yml',
                '--force' => true,
            ]
        );

        $doctrine->getManager()->clear();

        self::assertEquals(1, $countryRepository->count(['iso2Code' => 'AA']));
        self::assertTrue($countryRepository->find('XX')->isDeleted());
        self::assertEquals('QQLand', $countryRepository->find('QQ')->getName());
        self::assertEquals(1, $regionRepository->count(['combinedCode' => 'YY-01']));
        self::assertEquals(1, $regionRepository->count(['combinedCode' => 'AA-AA']));
        self::assertTrue($regionRepository->find('XX-XX')->isDeleted());
        self::assertTrue($regionRepository->find('QQ-QQ')->isDeleted());
        self::assertOutputContains($commandTester, 'Command run in force mode, updating DB');
        self::assertOutputNotContains(
            $commandTester,
            sprintf(
                "INSERT INTO oro_dictionary_country (iso2_code, iso3_code, name) VALUES('%s', '%s', '%s');",
                'AA',
                'AAA',
                'New A'
            )
        );

        self::assertOutputNotContains(
            $commandTester,
            sprintf(
                "UPDATE oro_dictionary_country SET name = '%s' WHERE iso2_code = '%s';",
                'QQLand',
                'QQ',
            )
        );

        self::assertOutputNotContains(
            $commandTester,
            sprintf(
                $expectedInsertRegionQuery,
                'YY-01',
                'YY',
                '01',
                'region.YY-01'
            )
        );

        self::assertOutputNotContains(
            $commandTester,
            sprintf(
                $expectedInsertRegionQuery,
                'AA-AA',
                'AA',
                'AA',
                'region.AA-AA'
            )
        );
    }
}
