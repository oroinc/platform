<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\AfterFeatureScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Doctrine\Inflector\Rules\English\InflectorFactory;
use Gaufrette\File;
use GuzzleHttp\Client;
use Oro\Bundle\EmailBundle\Tests\Behat\Context\EmailContext;
use Oro\Bundle\ImportExportBundle\Tests\Behat\Services\PreExportMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element as OroElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ImportExportContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    private ?OroMainContext $oroMainContext = null;

    private ?EmailContext $emailContext = null;

    /**
     * @var string Path to saved template
     */
    protected string $template = '';

    /**
     * @var string Path to import file
     */
    protected string $importFile = '';

    /**
     * @var string[] Paths to exported files
     */
    protected static array $exportFiles = [];

    private ?string $absoluteUrl = null;

    public function setAbsoluteUrl(?string $absoluteUrl): void
    {
        $this->absoluteUrl = $absoluteUrl;
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        if ($environment->hasContextClass(OroMainContext::class)) {
            $this->oroMainContext = $environment->getContext(OroMainContext::class);
        }
        if ($environment->hasContextClass(EmailContext::class)) {
            $this->emailContext = $environment->getContext(EmailContext::class);
        }
    }

    /**
     * @AfterFeature
     */
    public static function afterFeature(AfterFeatureScope $event)
    {
        static::$exportFiles = [];
    }

    /**
     * Open specific tab on multi-import modal
     *
     * @When /^(?:|I )open "(?P<tabLabel>([\w\s]+))" import tab$/
     * @param string $tabLabel
     */
    public function IOpenImportTab($tabLabel)
    {
        $this->closeFlashMessages();

        $this->openImportModalAndReturnImportSubmitButton();

        if (false === $this->isMultiImportModal()) {
            return;
        }

        $activeTab = $this->getPage()->find('css', '.import-widget-content .nav-tabs .active');
        $tabToBeActivated = $this->getPage()->findLink($tabLabel);

        self::assertNotNull($activeTab, 'There are currently no active tabs');
        self::assertNotNull($tabToBeActivated, 'Tab to be opened was not found');

        if ($tabToBeActivated->getText() === $activeTab->getText()) {
            return;
        }

        $tabToBeActivated->click();
    }

    //@codingStandardsIgnoreStart
    /**
     * Download data template from entity grid page with custom processor
     *
     * @When /^(?:|I )download "(?P<entity>([\w\s]+))" Data Template file with processor "(?P<processorName>([\w\s\.]+))"$/
     * @param string $entity
     * @param string $processorName
     */
    //@codingStandardsIgnoreEnd
    public function iDownloadDataTemplateFileWithProcessor($entity, $processorName)
    {
        $this->downloadTemplateFile($entity, $processorName);
    }

    /**
     * Download data template from entity grid page
     *
     * @When /^(?:|I )download "(?P<entity>([\w\s]+))" Data Template file$/
     * @param string $entity
     */
    public function iDownloadDataTemplateFile($entity)
    {
        $this->downloadTemplateFile($entity);
    }

    /**
     * @param string $entity
     * @param null   $processorName
     */
    public function downloadTemplateFile($entity, $processorName = null)
    {
        $entityClass = $this->getAppContainer()
            ->get('oro_entity.entity_alias_resolver')
            ->getClassByAlias($this->convertEntityNameToAlias($entity));
        $processors = $this->getAppContainer()
            ->get('oro_importexport.processor.registry')
            ->getProcessorAliasesByEntity('export_template', $entityClass);

        if (!$processorName) {
            self::assertCount(
                1,
                $processors,
                sprintf(
                    'Too many processors ("%s") for export "%s" entity',
                    implode(', ', $processors),
                    $entity
                )
            );
            $processor = reset($processors);
        } else {
            self::assertContains($processorName, $processors);
            $processor = $processorName;
        }

        $this->downloadTemplateFileByProcessor($processor);
    }

    /**
     * @param string $processor
     * @param array $options
     */
    public function downloadTemplateFileByProcessor($processor, $options = [])
    {
        $this->openImportModalAndReturnImportSubmitButton();

        $exportButton = $this->createElement('ActiveExportTemplateButton');
        self::assertTrue($exportButton->isIsset(), "Export template link was not found");

        $url = $this->locatePath($this->getAppContainer()->get('router')->generate(
            'oro_importexport_export_template',
            [
                'processorAlias' => $processor,
                'options' => $options
            ]
        ));

        $this->template = $this->getTempFilePath('import_template_');

        $cookieJar = $this->getCookieJar($this->getSession());
        $client = new Client();
        $response = $client->request('GET', $url, [
            'allow_redirects' => true,
            'cookies' => $cookieJar,
            'sink' => $this->template
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $this->oroMainContext->closeUiDialog();
    }

    /**
     * Runs export internally.
     *
     * @When /^(?:|I )run export for "(?P<entity>([\w\s]+))"$/
     */
    public function iRunExportForEntity(string $entity)
    {
        $this->performExportForEntity($entity, null);
    }

    /**
     * Runs export from UI.
     *
     * @When /^(?:|I )run export$/
     */
    public function iRunExport()
    {
        $this->closeFlashMessages();

        $exportButton = $this->createElement('MainExportFileButton');
        $exportButton->click();
    }

    public static function getRecentExportFilePath(): string
    {
        return end(static::$exportFiles);
    }

    public static function rememberExport(string $filePath): void
    {
        static::$exportFiles[] = $filePath;
    }

    /**
     * This method strictly compares data from the downloaded file
     *
     * @Given /^Exported file for "(?P<entity>([\w\s]+))" contains the following data:$/
     *
     * @param string $entity
     * @param TableNode $expectedEntities
     */
    public function exportedFileForEntityContainsFollowingData($entity, TableNode $expectedEntities)
    {
        $this->exportedFileForEntityWithProcessorContainsFollowingData($entity, $expectedEntities, null);
    }

    //@codingStandardsIgnoreStart
    /**
     * This method strictly compares data from the downloaded file
     *
     * @Given /^Exported file for "(?P<entity>([\w\s]+))" with processor "(?P<processorName>([\w\s\.]+))" contains the following data:$/
     *
     * @param string $entity
     * @param string|null $processorName
     * @param TableNode $expectedEntities
     */
    //@codingStandardsIgnoreEnd
    public function exportedFileForEntityWithProcessorContainsFollowingData(
        $entity,
        TableNode $expectedEntities,
        $processorName
    ) {
        $filePath = $this->performExportForEntity($entity, $processorName);

        try {
            $handler = fopen($filePath, 'rb');
            $headers = fgetcsv($handler, 1000, ',');
            $expectedHeaders = $expectedEntities->getRow(0);

            foreach ($expectedHeaders as $key => $expectedHeader) {
                static::assertEquals($expectedHeader, $headers[$key]);
            }

            $i = 1;
            while (($data = fgetcsv($handler, 1000, ',')) !== false) {
                $expectedEntityData = array_combine($headers, array_values($expectedEntities->getRow($i)));
                $entityDataFromCsv = array_combine($headers, array_values($data));

                foreach ($expectedEntityData as $property => $value) {
                    static::assertEquals($value, $entityDataFromCsv[$property]);
                }

                $i++;
            }
        } finally {
            fclose($handler);
        }

        static::assertCount($i, $expectedEntities->getRows());
    }

    /**
     * This method makes non-strict comparison of data from the downloaded file.
     *
     * Checks whether the listed columns (in any order) and corresponding data is present.
     *
     * @Given /^Exported file for "(?P<entity>([\w\s]+))" contains at least the following columns:$/
     *
     * @param string    $entity
     * @param TableNode $expectedEntities
     */
    public function exportedFileForEntityContainsAtLeastFollowingColumns($entity, TableNode $expectedEntities)
    {
        $this->exportedFileForEntityWithProcessorContainsAtLeastFollowingColumns($entity, $expectedEntities, null);
    }

    //@codingStandardsIgnoreStart
    /**
     * This method makes non-strict comparison of data from the downloaded file.
     *
     * Checks whether the listed columns (in any order) and corresponding data is present.
     *
     * @Given /^Exported file for "(?P<entity>([\w\s]+))" with processor "(?P<processorName>([\w\s\.]+))" contains at least the following columns:$/
     *
     * @param string      $entity
     * @param TableNode   $expectedEntities
     * @param string|null $processorName
     */
    //@codingStandardsIgnoreEnd
    public function exportedFileForEntityWithProcessorContainsAtLeastFollowingColumns(
        $entity,
        TableNode $expectedEntities,
        $processorName
    ) {
        $filePath = $this->performExportForEntity($entity, $processorName);

        $this->assertFileContainsAtLeastFollowingColumns($filePath, $expectedEntities);
    }

    /**
     * @Given /^exported file contains at least the following columns:$/
     * @Then /^(?:|I )download export file$/
     *
     * @param TableNode $expectedEntities
     */
    public function exportedFileContainsAtLeastFollowingColumns(?TableNode $expectedEntities = null)
    {
        $filePath = $this->getExportFile();

        if ($expectedEntities) {
            $this->assertFileContainsAtLeastFollowingColumns($filePath, $expectedEntities);
        }
    }

    private function assertFileContainsAtLeastFollowingColumns(string $filePath, TableNode $expectedEntities)
    {
        $exportedFile = new \SplFileObject($filePath, 'rb');

        // Treat file as CSV, skip empty lines.
        $exportedFile->setFlags(\SplFileObject::READ_CSV
            | \SplFileObject::READ_AHEAD
            | \SplFileObject::SKIP_EMPTY
            | \SplFileObject::DROP_NEW_LINE);

        $headers = $exportedFile->current();
        $expectedHeaders = $expectedEntities->getRow(0);

        $errors = [];
        foreach ($exportedFile as $line => $data) {
            $entityDataFromCsv = array_combine($headers, array_values($data));
            $expectedEntityData = array_combine($expectedHeaders, array_values($expectedEntities->getRow($line)));

            // Ensure that at least expected data is present.
            foreach ($expectedEntityData as $property => $value) {
                try {
                    if (preg_match('/\<contains\("(?P<needle>(?:[^"]|\\")+)"\)\>/i', $value, $matches)) {
                        static::assertMatchesRegularExpression(
                            sprintf('~%s~', $matches['needle']),
                            (string)$entityDataFromCsv[$property]
                        );
                    } elseif ($value === '<notEmpty()>') {
                        static::assertNotEmpty($entityDataFromCsv[$property]);
                    } elseif (preg_match('/\<absoluteUrl\("(?P<path>(?:[^"]|\\")+)"\)\>/i', $value, $matches)) {
                        static::assertEquals(
                            $this->getAbsoluteUrl($matches['path']),
                            $entityDataFromCsv[$property]
                        );
                    } else {
                        static::assertArrayHasKey(
                            $property,
                            $entityDataFromCsv,
                            sprintf('Failed asserting that the column "%s" exists in importing file', $property)
                        );
                        static::assertEquals(
                            $value,
                            $entityDataFromCsv[$property],
                            sprintf('Failed asserting that two columns "%s" are equal on row %d', $property, $line)
                        );
                    }
                } catch (Exception $exception) {
                    $message = $exception->getMessage();
                    if ($exception instanceof ExpectationFailedException && $exception->getComparisonFailure()) {
                        $message .= $exception->getComparisonFailure()->getDiff();
                    }
                    $errors[] = $message;
                }
            }
        }

        static::assertEmpty($errors, implode("\n\n", $errors));

        static::assertCount($exportedFile->key(), $expectedEntities->getRows());
    }

    /**
     * This method makes non-strict comparison of data from the downloaded file.
     *
     * Checks whether the listed rows (in any order) with given columns and corresponding data is present.
     *
     * @Given /^Exported file for "(?P<entity>([\w\s]+))" contains following rows in any order:$/
     *
     * @param string    $entity
     * @param TableNode $expectedEntities
     */
    public function exportedFileForEntityContainsFollowingRowsIAnyOrder(
        $entity,
        TableNode $expectedEntities
    ) {
        $filePath = $this->performExportForEntity($entity, null);

        $exportedFile = new \SplFileObject($filePath, 'rb');
        // Treat file as CSV, skip empty lines.
        $exportedFile->setFlags(\SplFileObject::READ_CSV
            | \SplFileObject::READ_AHEAD
            | \SplFileObject::SKIP_EMPTY
            | \SplFileObject::DROP_NEW_LINE);

        $expectedRows = $expectedEntities->getRows();
        foreach ($exportedFile as $line => $entityDataFromCsv) {
            static::assertNotFalse(array_search($entityDataFromCsv, $expectedRows));
        }

        static::assertCount($exportedFile->key(), $expectedEntities->getRows());
    }

    /**
     * @param string $entityName
     * @return string
     */
    protected function convertEntityNameToAlias($entityName)
    {
        $name = strtolower($entityName);
        $nameParts = explode(' ', $name);
        $nameParts = array_map([(new InflectorFactory())->build(), 'singularize'], $nameParts);

        return implode('', $nameParts);
    }

    /**
     * Assert that given column is NOT present on downloaded csv template
     * Example: When I download Data Template file
     *          Then I don't see Business Customer Name column
     *
     * @Then /^(?:|I )don't see (?P<column>([\w\s.+\/]+)) column$/
     */
    public function iDonTSeeColumn($column)
    {
        $csv = array_map('str_getcsv', file($this->template));
        self::assertNotContains($column, $csv[0]);
    }

    /**
     * Assert that given column is present in the downloaded csv template
     * Example: When I download Data Template file
     *          And I see Account Customer name column
     *
     * @Then /^(?:|I )see (?P<column>([\w\s.+\/]+)) column$/
     * @Then /^(?:|I )see (?P<column>([\w\s.+\/]+)) column is present in the downloaded csv template$/
     */
    public function iSeeColumn($column)
    {
        $csv = array_map('str_getcsv', file($this->template));
        self::assertContains($column, $csv[0]);
    }

    /**
     * Assert that given columns are present in the downloaded csv template
     * Example: When I download Data Template file
     *          And I see the following columns in the downloaded csv template:
     *              | sku    |
     *              | status |
     *              | type   |
     *
     * @Then /^(?:|I )see the following columns in the downloaded csv template:$/
     */
    public function iSeeColumns(TableNode $table)
    {
        $csv = array_map('str_getcsv', file($this->template));
        $rows = array_column($table->getRows(), 0);
        foreach ($rows as $row) {
            self::assertContains($row, $csv[0]);
        }
    }

    /**
     * Assert that given columns are present in the downloaded csv template
     * Example: When I download Data Template file
     *          And I see the following columns in exact order in the downloaded csv template:
     *              | sku    |
     *              | status |
     *              | type   |
     *
     * @Then /^(?:|I )see the following columns in exact order in the downloaded csv template:$/
     */
    public function iSeeColumnsInExactOrder(TableNode $table)
    {
        $csv = array_map('str_getcsv', file($this->template));
        $rows = array_column($table->getRows(), 0);
        foreach ($rows as $i => $row) {
            self::assertEquals($row, $csv[0][$i] ?? '');
        }
    }

    /**
     * Fill downloaded csv file template
     * Example: And I fill template with data:
     *            | Account Customer name | Channel Name        | Opportunity name | Status Id   |
     *            | Charlie               | First Sales Channel | Opportunity one  | in_progress |
     *            | Samantha              | First Sales Channel | Opportunity two  | in_progress |
     *
     * @Given /^(?:|I )fill template with data:$/
     */
    public function iFillTemplateWithData(TableNode $table)
    {
        $this->importFile = $this->getTempFilePath('import_data_');
        $fp = fopen($this->importFile, 'w');
        $csv = array_map('str_getcsv', file($this->template));
        $headers = array_shift($csv);

        $headers = array_unique(array_merge($headers, $table->getRow(0)));
        fputcsv($fp, $headers);

        foreach ($table as $row) {
            $values = [];
            foreach ($headers as $header) {
                $value = '';
                foreach ($row as $rowHeader => $rowValue) {
                    if ($rowHeader === $header || preg_match(sprintf('/^%s$/i', $rowHeader), $header)) {
                        $value = $rowValue;
                    }
                }

                $values[] = $this->processFunctions($value);
            }
            fputcsv($fp, $values);
        }
    }

    private function processFunctions(string $value): string
    {
        switch (true) {
            case preg_match(
                '/\<valueFromExportFile\("(?P<column>(?:[^"]|\\")+)",\s?'
                . '"(?P<searchedColumn>(?:[^"]|\\")+)",\s?'
                . '"(?P<searchedColumnValue>(?:[^"]|\\")+)"\)\>/i',
                $value,
                $matches
            ):
                $value = $this->getValueFromExportFile(
                    $matches['searchedColumn'],
                    $matches['searchedColumnValue'],
                    $matches['column']
                );

                self::assertNotNull(
                    $value,
                    sprintf(
                        'Searched value \"%s\" in column \"%s\" not found in export file',
                        $matches['searchedColumnValue'],
                        $matches['searchedColumn']
                    )
                );
                break;

            case preg_match('/\<absoluteUrl\("(?P<path>(?:[^"]|\\")+)"\)\>/i', $value, $matches):
                $value = $this->getAbsoluteUrl($matches['path']);
                break;

            case preg_match('/\<absolutePath\("(?P<path>(?:[^"]|\\")+)"\)\>/i', $value, $matches):
                $value = $this->getAbsolutePath($matches['path']);
                break;

            case preg_match('/\<eol\("(?P<value>(?:[^"]|\\")+)"\)\>/i', $value, $matches):
                $value = str_replace('\r\n', PHP_EOL, $matches['value']);
                break;
        }

        return $value;
    }

    private function getAbsoluteUrl(string $path): string
    {
        if (!$this->absoluteUrl) {
            $this->absoluteUrl = $this->getAppContainer()->get('oro_config.manager')->get('oro_ui.application_url');
        }

        return sprintf('%s/%s', $this->absoluteUrl, ltrim($path, '/'));
    }

    private function getAbsolutePath(string $path): string
    {
        return sprintf('%s/%s', $this->getAppContainer()->getParameter('kernel.project_dir'), ltrim($path, '/'));
    }

    /**
     * Fill import csv file
     * Example: And I fill import file with data:
     *            | Account Customer name | Channel Name        | Opportunity name | Status Id   |
     *            | Charlie               | First Sales Channel | Opportunity one  | in_progress |
     *            | Samantha              | First Sales Channel | Opportunity two  | in_progress |
     *
     * @Given /^(?:|I )fill import file with data:$/
     */
    public function iFillImportFileWithData(TableNode $table)
    {
        $this->importFile = $this->getTempFilePath('import_data_');
        $fp = fopen($this->importFile, 'w');

        fputcsv($fp, $table->getRow(0));

        foreach ($table as $row) {
            $values = [];
            foreach ($row as $rowHeader => $rowValue) {
                $values[] = $this->processFunctions($rowValue);
            }

            fputcsv($fp, $values);
        }
    }

    /**
     * Takes from exported file the value of $column from row where $searchedColumn equals $searchedColumnValue
     *
     * @param string $searchedColumn
     * @param string $searchedColumnValue
     * @param string $column
     *
     * @return string|null Value of $column in row where column $searchedColumn equals $searchedColumnValue
     */
    private function getValueFromExportFile(
        string $searchedColumn,
        string $searchedColumnValue,
        string $column
    ): ?string {
        $filePath = static::getRecentExportFilePath() ?: $this->getExportFile();
        $export = $this->parseCsv($filePath);

        self::assertArrayHasKey($column, $export[0], sprintf('Export file does not contain column %s', $column));

        self::assertArrayHasKey(
            $searchedColumn,
            $export[0],
            sprintf('Export file does not contain searched column %s', $searchedColumn)
        );

        $foundValue = null;
        foreach ($export as $exportRow) {
            if ($exportRow[$searchedColumn] === $searchedColumnValue) {
                $foundValue = $exportRow[$column];
                break;
            }
        }

        return $foundValue;
    }

    private function parseCsv(string $csvFilePath): array
    {
        $csvData = array_map('str_getcsv', file($csvFilePath));
        array_walk($csvData, static function (&$row) use ($csvData) {
            $row = array_combine($csvData[0], $row);
        });
        array_shift($csvData);

        return $csvData;
    }

    /**
     * @When /^(?:|I )save import file with BOM/
     */
    public function iSaveImportFileWithBOM()
    {
        $newImportFile = $this->getTempFilePath('import_data_');

        file_put_contents($newImportFile, pack('H*', 'EFBBBF'));

        $readTheFile = function ($path) {
            $handle = fopen($path, "r");

            while (!feof($handle)) {
                yield fgets($handle);
            }

            fclose($handle);
        };

        $modifiedImportFile = fopen($newImportFile, 'a');
        $iterator = $readTheFile($this->importFile);

        foreach ($iterator as $iteration) {
            fputs($modifiedImportFile, $iteration);
        }

        fclose($modifiedImportFile);
        unlink($this->importFile);

        $this->importFile = $newImportFile;
    }

    /**
     * Import downloaded template file without changes
     *
     * @When /^(?:|I )import downloaded template file$/
     */
    public function iImportDownloadedTemplate()
    {
        $this->importFile = $this->template;
        $this->iImportFile();
    }

    /**
     * Import filled file
     *
     * @When /^(?:|I )import file$/
     */
    public function iImportFile()
    {
        $this->tryImportFile();

        $flashMessage = 'Import started successfully. You will receive an email notification upon completion.';
        $this->oroMainContext->iShouldSeeFlashMessage($flashMessage);
        usleep(150000);
    }

    /**
     * @When /^(?:|I )import file with strategy "(?P<strategy>([\w\s\.]+))"$/
     */
    public function iImportFileWithStrategy($strategy)
    {
        $this->tryImportFileWithStrategy($strategy);

        $flashMessage = 'Import started successfully. You will receive an email notification upon completion.';
        $this->oroMainContext->iShouldSeeFlashMessage($flashMessage);
    }

    /**
     * Expect that it will show errors
     *
     * @When /^(?:|I )try import file$/
     */
    public function tryImportFile()
    {
        $importSubmitButton = $this->openImportModalAndReturnImportSubmitButton();

        $this->createElement('ActiveImportFileField')->attachFile($this->importFile);

        $importSubmitButton->press();
        $this->getDriver()->waitForAjax(240000); // wait max 4 minutes
    }

    /**
     * @When /^(?:|I )try import file with strategy "(?P<strategy>([\w\s\.]+))"$/
     */
    public function tryImportFileWithStrategy($strategy)
    {
        $importSubmitButton = $this->openImportModalAndReturnImportSubmitButton();
        $this->createElement('ActiveImportFileField')->attachFile($this->importFile);
        $this->createElement('ActiveImportStrategyField')->selectOption($strategy);

        $importSubmitButton->press();
        $this->getDriver()->waitForAjax(240000);
    }

    /**
     * Example: When I validate file
     *
     * @When /^(?:|I )validate file$/
     */
    public function iValidateFile()
    {
        $importSubmitButton = $this->openImportModalAndReturnValidateButton();
        $this->createElement('ActiveImportFileField')->attachFile($this->importFile);

        $importSubmitButton->press();
        $this->getDriver()->waitForAjax(240000); // wait max 4 minutes

        $flashMessage = 'Validation started successfully. You will receive an email notification upon completion.';
        $this->oroMainContext->iShouldSeeFlashMessage($flashMessage);
    }

    /**
     * Example: When I validate file with strategy "Reset and Add"
     *
     * @When /^(?:|I )validate file with strategy "(?P<strategy>([\w\s\.]+))"$/
     */
    public function iValidateFileWithStrategy($strategy)
    {
        $validateSubmitButton = $this->openImportModalAndReturnValidateButton();
        $this->createElement('ActiveImportFileField')->attachFile($this->importFile);
        $this->createElement('ActiveImportStrategyField')->selectOption($strategy);

        $validateSubmitButton->press();
        $this->getDriver()->waitForAjax(240000); // wait max 4 minutes

        $flashMessage = 'Validation started successfully. You will receive an email notification upon completion.';
        $this->oroMainContext->iShouldSeeFlashMessage($flashMessage);
    }

    /**
     * @When /^I import exported file$/
     */
    public function iImportExportedFile()
    {
        // BAP-17638: Replace sleep to appropriate logic
        sleep(2);

        // BAP-17638: Replace sleep to appropriate logic
        // temporary solution: find the most recent file created in import_export dir
        $fileManager = $this->getAppContainer()->get('oro_importexport.file.file_manager');
        $files = $fileManager->getFilesByPeriod();

        $exportFiles = array_filter($files, function (File $file) {
            return preg_match('/export_\d{4}.*.csv/', $file->getName());
        });

        // sort by modification date
        usort($exportFiles, function (File $a, File $b) {
            return $b->getMtime() <=> $a->getMtime();
        });

        /** @var File $exportFile */
        $exportFile = reset($exportFiles);
        $this->importFile = $fileManager->writeToTmpLocalStorage($exportFile->getName());
        $this->tryImportFile();
    }

    /**
     * Assert validation messages in import
     * Example: When I try import file
     *          Then I should see validation message "Error in row #1. Account name: This value should not be blank."
     *
     * @Then /^(?:|I )should see validation message "(?P<validationMessage>[^"]+)"$/
     */
    public function iShouldSeeValidationMessage($validationMessage)
    {
        $errorsHolder = $this->createElement('ImportErrors');
        self::assertTrue($errorsHolder->isValid(), 'No import errors found');

        $errors = $errorsHolder->findAll('css', 'ol li');
        $existedErrors = [];

        /** @var NodeElement $error */
        foreach ($errors as $error) {
            $error = $error->getHtml();
            $existedErrors[] = $error;
            if (false !== stripos($error, $validationMessage)) {
                return;
            }
        }

        self::fail(sprintf(
            '"%s" error message not found in errors: "%s"',
            $validationMessage,
            implode('", "', $existedErrors)
        ));
    }

    /**
     * @Given /^I change the export batch size to (?P<size>\d+)/
     */
    public function changeExportBatchSize(int $size): void
    {
        // oro_importexport.test.cache service is defined in ImportExportBundle/Tests/Behat/parameters.yml
        $cache = $this->getAppContainer()->get('oro_importexport.test.cache');
        $item = $cache->getItem(PreExportMessageProcessor::BATCH_SIZE_KEY);
        $item->set($size);
        $cache->save($item);
    }

    /**
     * @return OroElement
     */
    protected function openImportModalAndReturnImportSubmitButton()
    {
        $this->closeFlashMessages();

        $importSubmitButton = $this->createElement('ImportModalImportFileButton');

        if (false === $importSubmitButton->isIsset()) {
            $mainImportButton =$this->createElement('MainImportFileButton');
            self::assertNotNull($mainImportButton, 'Main import button was not found');
            $mainImportButton->click();
            $this->waitForAjax();
        }

        return $importSubmitButton;
    }

    /**
     * @return OroElement
     */
    protected function openImportModalAndReturnValidateButton()
    {
        $this->closeFlashMessages();

        $validateFileButton = $this->createElement('Validate File Button');

        if (false === $validateFileButton->isIsset()) {
            $mainImportButton = $this->createElement('MainImportFileButton');
            self::assertNotNull($mainImportButton, 'Main import button was not found');
            $mainImportButton->click();
            $this->waitForAjax();
        }

        return $validateFileButton;
    }

    protected function isMultiImportModal(): bool
    {
        return $this->createElement('ImportNavTabsContainer')->isIsset();
    }

    /**
     * Performs export internally.
     *
     * @param string $entity Entity class alias.
     * @param string $processorName export processor name.
     *
     * @return string Filepath to exported file.
     */
    private function performExportForEntity($entity, $processorName = null)
    {
        $entityClass = $this->getAppContainer()
            ->get('oro_entity.entity_alias_resolver')
            ->getClassByAlias($this->convertEntityNameToAlias($entity));

        if (!$processorName) {
            $processors = $this->getAppContainer()
                ->get('oro_importexport.processor.registry')
                ->getProcessorAliasesByEntity('export', $entityClass);
            self::assertCount(1, $processors, sprintf(
                'Too many processors ("%s") for export "%s" entity',
                implode(', ', $processors),
                $entity
            ));
            $processorName = array_shift($processors);
        }

        $filePath = $this->getTempFilePath('export_');

        $jobExecutor = $this->getAppContainer()->get('oro_importexport.job_executor');
        $jobResult = $jobExecutor->executeJob(
            'export',
            'entity_export_to_csv',
            [
                'export' => [
                    'processorAlias' => $processorName,
                    'entityName' => $entityClass,
                    'filePath' => $filePath,
                ]
            ]
        );

        static::assertTrue($jobResult->isSuccessful());

        static::rememberExport($filePath);

        return $filePath;
    }

    private function getExportFile(): string
    {
        $exportFileUrl = $this->emailContext->getLinkUrlFromEmail('Download');

        self::assertNotNull($exportFileUrl, 'Could not find "Download" link in email');

        $filePath = $this->getTempFilePath('export_');

        $cookieJar = $this->getCookieJar($this->getSession());
        $client = new Client([
            'allow_redirects' => true,
            'cookies' => $cookieJar,
            'sink' => $filePath
        ]);

        $this->spin(static function () use ($client, $exportFileUrl, &$response) {
            $response = $client->get($exportFileUrl);

            return $response->getStatusCode() === 200;
        });

        self::assertFileExists($filePath, 'Failed to find a link and download an export file from email');

        static::rememberExport($filePath);

        return $filePath;
    }

    /**
     * Returns the path in /tmp directory where to store temporary files
     */
    private function getTempFilePath(string $prefix): string
    {
        $path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'importexport';
        if (!is_dir($path)) {
            mkdir($path);
        }

        return tempnam($path, $prefix) . '.csv';
    }

    private function closeFlashMessages()
    {
        $flashMessages = $this->findAllElements('Flash Message');
        foreach ($flashMessages as $flashMessage) {
            if ($flashMessage->isValid() && $flashMessage->isVisible()) {
                /** @var NodeElement $closeButton */
                $closeButton = $flashMessage->find('css', '[data-dismiss="alert"]');
                $closeButton->press();
            }
        }
    }
}
