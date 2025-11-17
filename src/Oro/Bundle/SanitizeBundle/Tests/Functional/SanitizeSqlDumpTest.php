<?php

namespace Oro\Bundle\SanitizeBundle\Tests\Functional;

use Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Entity\TestSanitizable;
use Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Provider\EntityAllMetadataProviderDecorator;
use Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Provider\Rule\FileBasedProviderDecorator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\TempDirExtension;

/**
 * @dbIsolationPerTest
 */
class SanitizeSqlDumpTest extends WebTestCase
{
    use TempDirExtension;

    private string $outputFile;

    #[\Override]
    protected function setup(): void
    {
        $this->initClient();

        $this->outputFile = $this->getTempFile('sanitize_sql_dump', 'sanitize_dump'. '.sql');
        $metadataProvider = $this->getContainer()->get(EntityAllMetadataProviderDecorator::class);
        $metadataProvider->setEntitiesToFilter([TestSanitizable::class]);
    }

    /**
     * @dataProvider validCasesDataProvider
     */
    public function testSuccessfulSanitizeSqlDumpWithoutFileOutput(
        array $ruleConfigFiles,
        string $expectedResponse
    ): void {
        $fileBasedRulesProvider = $this->getContainer()->get(FileBasedProviderDecorator::class);
        $fileBasedRulesProvider->setRuleFiles($ruleConfigFiles);

        self::assertEquals(
            $expectedResponse,
            $this->runCommand('oro:sanitize:dump-sql', [], false)
        );
    }

    /**
     * @dataProvider validCasesDataProvider
     */
    public function testSuccessfulSanitizeSqlDumpWithFileOutput(
        array $ruleConfigFiles,
        string $expectedResponse
    ): void {
        $this->getContainer()
            ->get(FileBasedProviderDecorator::class)
            ->setRuleFiles($ruleConfigFiles);

        $this->runCommand('oro:sanitize:dump-sql', [$this->outputFile], true);
        self::assertEquals($expectedResponse, trim(file_get_contents($this->outputFile)));
    }

    public function validCasesDataProvider(): array
    {
        return [
            [
                [],
                $this->getExpectedFromFile('success/no_file_config.out')
            ],
            [
                ['valid/config_1.yml'],
                $this->getExpectedFromFile('success/single_file_config_1.out')],
            [
                ['valid/config_2.yml'],
                $this->getExpectedFromFile('success/single_file_config_2.out')
            ],
            [
                ['valid/config_1.yml', 'valid/config_2.yml'],
                 $this->getExpectedFromFile('success/multiple_file_config_1.out')
            ],
            [
                ['valid/config_2.yml', 'valid/config_1.yml'],
                $this->getExpectedFromFile('success/multiple_file_config_2.out')
            ]
        ];
    }

    public function testSuccessfulSanitizeSqlDumpWithSkipGuessing(): void
    {
        $fileBasedRulesProvider = $this->getContainer()->get(FileBasedProviderDecorator::class);
        $fileBasedRulesProvider->setRuleFiles(['valid/config_1.yml']);

        self::assertEquals(
            $this->getExpectedFromFile('success/single_file_config_1_no_guessing.out'),
            $this->runCommand('oro:sanitize:dump-sql', ['--no-guessing' => true], false)
        );
    }

    public function testSqlValidationFailed(): void
    {
        $fileBasedRulesProvider = $this->getContainer()->get(FileBasedProviderDecorator::class);
        $fileBasedRulesProvider->setRuleFiles(['invalid/invalid_raw_sql_syntax.yml']);
        @unlink($this->outputFile);

        self::assertStringContainsString(
            'The sanitizing SQL queries validation has detected the following errors',
            $this->runCommand('oro:sanitize:dump-sql')
        );
        self::assertFileDoesNotExist($this->outputFile);
    }

    public function testFieldRuleIncompatibility(): void
    {
        $fileBasedRulesProvider = $this->getContainer()->get(FileBasedProviderDecorator::class);
        $fileBasedRulesProvider->setRuleFiles(['invalid/field_rule_incompatibility.yml']);
        @unlink($this->outputFile);

        $testEntityClass = TestSanitizable::class;
        $commandOutput = $this->runCommand('oro:sanitize:dump-sql');

        self::assertStringContainsString(
            'When building sanitizing SQL queries, the following issues/errors were uncovered',
            $commandOutput
        );
        self::assertStringContainsString(
            "The specified sanitizing rule 'md5' cannot be applied to the non-string field 'birthday'"
            . " of the '$testEntityClass' entity",
            $commandOutput
        );
        self::assertStringContainsString(
            "The specified sanitizing rule 'date' cannot be applied to the non-date field 'secret'"
            . " of the '$testEntityClass' entity",
            $commandOutput
        );
        self::assertStringContainsString(
            "The specified sanitizing rule 'email' cannot be applied to the non-string field 'stateData'"
            . " of the '$testEntityClass' entity",
            $commandOutput
        );
        self::assertStringContainsString(
            "The specified sanitizing rule 'date' cannot be applied to the non-date field 'emailunguessable'"
            . " of the '$testEntityClass' entity",
            $commandOutput
        );
        self::assertStringContainsString(
            "The specified sanitizing rule 'attachment' cannot be applied"
            . " to the non-long text field 'first_custom_field'"
            . " of the '$testEntityClass' entity",
            $commandOutput
        );
        self::assertStringContainsString(
            "The specified sanitizing rule 'digits_mask' cannot be applied to the non-string field 'email_wrong_type'"
            . " of the '$testEntityClass' entity",
            $commandOutput
        );

        self::assertFileDoesNotExist($this->outputFile);
    }

    private function getExpectedFromFile(string $file): string
    {
        $filePath = str_replace('/', DIRECTORY_SEPARATOR, __DIR__. '/DataFixtures/expected_dump_results/' . $file);

        return trim(file_get_contents($filePath));
    }
}
