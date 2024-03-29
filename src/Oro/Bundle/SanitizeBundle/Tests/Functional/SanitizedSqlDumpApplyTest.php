<?php

namespace Oro\Bundle\SanitizeBundle\Tests\Functional;

use Doctrine\DBAL\Connection;
use Oro\Bundle\SanitizeBundle\Tests\Functional\DataFixtures\LoadTestSanitizableData;
use Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Entity\TestSanitizable;
use Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Provider\EntityAllMetadataProviderDecorator;
use Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Provider\Rule\FileBasedProviderDecorator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class SanitizedSqlDumpApplyTest extends WebTestCase
{
    private const CUSTOM_EMAIL_DOMAIN = 'example.com';

    private ?Connection $connection = null;

    protected function setup(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadTestSanitizableData::class]);

        $this->connection = self::getContainer()
            ->get('doctrine')
            ->getManager()
            ->getConnection();
        $this->outputFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sanitize_dump.sql';
        $metatdataProvider = $this->getContainer()->get(EntityAllMetadataProviderDecorator::class);
        $metatdataProvider->setEntitiesToFilter([TestSanitizable::class]);
    }

    public function testSanitizeAppliedProperlyWithoutCustomEmailDomain(): void
    {
        [$data, $serializedData] = $this->applyRulesAndReadAffectedData();

        // check data that must not be affected
        self::assertEquals('John', $data['first_name']);
        self::assertStringContainsString('1970-01-01', $data['birthday']);
        self::assertEquals('john.redison.reserve@example.com', $data['emailunguessable']);
        self::assertEquals('3333-444', $data['phone_second']);

        // guessed 'md5' rule processor results check
        self::assertMatchesRegularExpression('/[a-z0-9]{32}/', $data['middle_name']);
        // guessed 'md5' rule processor results check
        self::assertMatchesRegularExpression('/[a-z0-9]{32}/', $data['last_name']);
        // guessed 'email' rule processor results check
        self::assertMatchesRegularExpression('/john\\.redison\\d+@[a-z0-9]{32}\\.test/', $data['email']);
        // 'digits_mask' rule processor with custom options results check
        self::assertMatchesRegularExpression('/1 800 \\d{3}-\\d{3}-\\d{4}/', $data['phone']);
        // guessed 'md5' rule processor results check
        self::assertMatchesRegularExpression('/[a-z0-9]{32}/', $data['secret']);
        // guessed 'email' rule processor results check
        self::assertMatchesRegularExpression(
            '/john\\.redison\\.third\\d+@[a-z0-9]{32}\\.test/',
            $serializedData['email_third']
        );
        // 'date' rule processor results check. Today's date is newer then beging of 1970
        self::assertTrue(new \DateTime($serializedData['custom_event_date']) > new \DateTime('1970-01-02'));
        // 'null' rule processor results check for calar field
        self::assertNull($serializedData['first_custom_field']);
        // 'null' rule processor results check for array field
        self::assertEquals([], $this->connection->convertToPHPValue($data['state_data'], 'array'));
    }

    public function testSanitizeAppliedProperlyWithCustomEmailDomain(): void
    {
        self::getContainer()
            ->get('oro_sanitize.test.rule.email_field_processor')
            ->setCustomEmailDomain(self::CUSTOM_EMAIL_DOMAIN);

        [$data, $serializedData] = $this->applyRulesAndReadAffectedData();

        $pregQuotedEailDomain = preg_quote(self::CUSTOM_EMAIL_DOMAIN);
        self::assertMatchesRegularExpression('/john\\.redison\\d+@' . $pregQuotedEailDomain . '/', $data['email']);
        self::assertMatchesRegularExpression(
            '/john\\.redison\\.third\\d+@' . $pregQuotedEailDomain . '/',
            $serializedData['email_third']
        );
    }

    private function applyRulesAndReadAffectedData(): array
    {
        $fileBasedRulesProvider = $this->getContainer()->get(FileBasedProviderDecorator::class);
        $fileBasedRulesProvider->setRuleFiles(['valid/applicable_config.yml']);

        $this->runCommand('oro:sanitize:dump-sql', [$this->outputFile], true);
        $sanitizeSql = file_get_contents($this->outputFile);

        $this->connection->executeQuery($sanitizeSql);
        $sql = 'SELECT * FROM test_sanitizable_entity LIMIT 1';
        $data = $this->connection->fetchAssoc($sql);
        $serializedData = json_decode($data['serialized_data'], true);

        return [$data, $serializedData];
    }
}
