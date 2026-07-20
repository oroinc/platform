<?php

namespace Oro\Bundle\SanitizeBundle\Tests\Functional;

use Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Entity\TestSanitizableWithCustomIdColumn;
use Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Provider\EntityAllMetadataProviderDecorator;
use Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Provider\Rule\FileBasedProviderDecorator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\TempDirExtension;

/**
 * @dbIsolationPerTest
 */
class SanitizeSqlDumpCustomIdColumnTest extends WebTestCase
{
    use TempDirExtension;

    private string $outputFile;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->outputFile = $this->getTempFile('sanitize_sql_dump_custom_id_column', 'sanitize_dump.sql');

        // Process only the entity under test; let the email rule be guessed (no rule files).
        self::getContainer()
            ->get(EntityAllMetadataProviderDecorator::class)
            ->setEntitiesToFilter([TestSanitizableWithCustomIdColumn::class]);
        self::getContainer()
            ->get(FileBasedProviderDecorator::class)
            ->setRuleFiles([]);
    }

    public function testGeneratedSanitizeSqlReferencesIdentifierColumn(): void
    {
        // --skip-validate-sql so the raw generated SQL is dumped even when it is not applicable yet.
        $this->runCommand('oro:sanitize:dump-sql', [$this->outputFile, '--skip-validate-sql' => true], true);

        self::assertEquals(
            $this->getExpectedFromFile('success/custom_id_column.out'),
            trim(file_get_contents($this->outputFile))
        );
    }

    private function getExpectedFromFile(string $file): string
    {
        $filePath = str_replace('/', DIRECTORY_SEPARATOR, __DIR__ . '/DataFixtures/expected_dump_results/' . $file);

        return trim(file_get_contents($filePath));
    }
}
