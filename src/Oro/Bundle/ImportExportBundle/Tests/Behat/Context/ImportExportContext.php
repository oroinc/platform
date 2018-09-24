<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Session;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Doctrine\Common\Inflector\Inflector;
use Gaufrette\File;
use Guzzle\Http\Client;
use Guzzle\Plugin\Cookie\Cookie;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element as OroElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ImportExportContext extends OroFeatureContext implements
    KernelAwareContext,
    OroPageObjectAware
{
    use KernelDictionary, PageObjectDictionary;

    /**
     * @var EntityAliasResolver
     */
    private $aliasResolver;

    /**
     * @var ProcessorRegistry
     */
    private $processorRegistry;

    /**
     * @var OroMainContext
     */
    private $oroMainContext;

    /**
     * @param EntityAliasResolver $aliasResolver
     * @param ProcessorRegistry $processorRegistry
     */
    public function __construct(EntityAliasResolver $aliasResolver, ProcessorRegistry $processorRegistry)
    {
        $this->aliasResolver = $aliasResolver;
        $this->processorRegistry = $processorRegistry;
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->oroMainContext = $environment->getContext(OroMainContext::class);
    }

    /**
     * @var string Path to saved template
     */
    protected $template;

    /**
     * @var string Path to import file
     */
    protected $importFile;

    /**
     * Open specific tab on multi-import modal
     *
     * @When /^(?:|I )open "(?P<tabLabel>([\w\s]+))" import tab$/
     * @param string $tabLabel
     */
    public function IOpenImportTab($tabLabel)
    {
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
        $entityClass = $this->aliasResolver->getClassByAlias($this->convertEntityNameToAlias($entity));
        $processors = $this->processorRegistry->getProcessorAliasesByEntity('export_template', $entityClass);

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

        $url = $this->locatePath($this->getContainer()->get('router')->generate(
            'oro_importexport_export_template',
            [
                'processorAlias' => $processor,
                'options' => $options
            ]
        ));
        $this->template = tempnam(
            $this->getKernel()->getProjectDir().DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'import_export',
            'import_template_'
        );

        $cookieJar = $this->getCookieJar($this->getSession());
        $client = new Client($this->getSession()->getCurrentUrl());
        $client->addSubscriber(new CookiePlugin($cookieJar));
        $request = $client->get($url, null, ['save_to' => $this->template]);
        $response = $request->send();

        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * This method strictly compares data from the downloaded file
     *
     * @Given /^Exported file for "(?P<entity>([\w\s]+))" contains the following data:$/
     *
     * @param string $entity
     * @param TableNode $expectedEntities
     */
    public function exportedFileContainsFollowingData($entity, TableNode $expectedEntities)
    {
        $this->exportedFileWithProcessorContainsFollowingData($entity, $expectedEntities, null);
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
    public function exportedFileWithProcessorContainsFollowingData($entity, TableNode $expectedEntities, $processorName)
    {
        $filePath = $this->performExport($entity, $processorName);

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
            unlink($filePath);
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
    public function exportedFileContainsAtLeastFollowingColumns($entity, TableNode $expectedEntities)
    {
        $this->exportedFileWithProcessorContainsAtLeastFollowingColumns($entity, $expectedEntities, null);
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
    public function exportedFileWithProcessorContainsAtLeastFollowingColumns(
        $entity,
        TableNode $expectedEntities,
        $processorName
    ) {
        $filePath = $this->performExport($entity, $processorName);

        try {
            $exportedFile = new \SplFileObject($filePath, 'rb');
            // Treat file as CSV, skip empty lines.
            $exportedFile->setFlags(\SplFileObject::READ_CSV
                | \SplFileObject::READ_AHEAD
                | \SplFileObject::SKIP_EMPTY
                | \SplFileObject::DROP_NEW_LINE);

            $headers = $exportedFile->current();
            $expectedHeaders = $expectedEntities->getRow(0);

            foreach ($exportedFile as $line => $data) {
                $entityDataFromCsv = array_combine($headers, array_values($data));
                $expectedEntityData = array_combine($expectedHeaders, array_values($expectedEntities->getRow($line)));

                // Ensure that at least expected data is present.
                foreach ($expectedEntityData as $property => $value) {
                    static::assertEquals($value, $entityDataFromCsv[$property]);
                }
            }

            static::assertCount($exportedFile->key(), $expectedEntities->getRows());
        } finally {
            // We have to release SplFileObject before trying to delete the underlying file.
            $exportedFile = null;
            unlink($filePath);
        }
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
    public function exportedFileContainsFollowingRowsIAnyOrder(
        $entity,
        TableNode $expectedEntities
    ) {
        $filePath = $this->performExport($entity, null);

        try {
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
        } finally {
            // We have to release SplFileObject before trying to delete the underlying file.
            $exportedFile = null;
            unlink($filePath);
        }
    }

    /**
     * @param string $entityName
     * @return string
     */
    protected function convertEntityNameToAlias($entityName)
    {
        $name = strtolower($entityName);
        $nameParts = explode(' ', $name);
        $nameParts = array_map([new Inflector(), 'singularize'], $nameParts);

        return implode('', $nameParts);
    }

    /**
     * Assert that given column is NOT present on downloaded csv template
     * Example: When I download Data Template file
     *          Then I don't see Business Customer Name column
     *
     * @Then /^(?:|I )don't see (?P<column>([\w\s]+)) column$/
     */
    public function iDonTSeeColumn($column)
    {
        $csv = array_map('str_getcsv', file($this->template));
        self::assertNotContains($column, $csv[0]);
    }

    /**
     * Assert that given column is present on downloaded csv template
     * Example: When I download Data Template file
     *          And I see Account Customer name column
     *
     * @Then /^(?:|I )see (?P<column>([\w\s.+\/]+)) column$/
     */
    public function iSeeColumn($column)
    {
        $csv = array_map('str_getcsv', file($this->template));
        self::assertContains($column, $csv[0]);
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
        $this->importFile = tempnam(
            $this->getKernel()->getProjectDir().DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'import_export',
            'import_data_'
        );
        $fp = fopen($this->importFile, 'w');
        $csv = array_map('str_getcsv', file($this->template));
        $headers = array_shift($csv);
        fputcsv($fp, $headers);

        foreach ($table as $row) {
            $values = [];
            foreach ($headers as $header) {
                $value = '';
                foreach ($row as $rowHeader => $rowValue) {
                    if (preg_match(sprintf('/^%s$/i', $rowHeader), $header)) {
                        $value = $rowValue;
                    }
                }

                $values[] = $value;
            }
            fputcsv($fp, $values);
        }
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
        $this->waitForAjax();
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
        $this->waitForAjax();

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
        $fileManager = $this->getContainer()->get('oro_importexport.file.file_manager');
        $files = $fileManager->getFilesByPeriod();

        $exportFiles = array_filter($files, function (File $file) {
            return preg_match('/export_\d{4}.*.csv/', $file->getName());
        });

        // sort by modification date
        usort($exportFiles, function (File $a, File $b) {
            return $b->getMtime() > $a->getMtime();
        });

        /** @var File $exportFile */
        $exportFile = reset($exportFiles);
        $path = $this->getContainer()->getParameter('kernel.project_dir')
            .DIRECTORY_SEPARATOR
            .'var'
            .DIRECTORY_SEPARATOR
            .'import_export';
        $this->importFile = $path . DIRECTORY_SEPARATOR . $exportFile->getName();
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
     * @return OroElement
     */
    protected function openImportModalAndReturnImportSubmitButton()
    {
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
        $validateFileButton = $this->createElement('Validate File Button');

        if (false === $validateFileButton->isIsset()) {
            $mainImportButton = $this->createElement('MainImportFileButton');
            self::assertNotNull($mainImportButton, 'Main import button was not found');
            $mainImportButton->click();
            $this->waitForAjax();
        }

        return $validateFileButton;
    }

    /**
     * @return bool
     */
    protected function isMultiImportModal(): bool
    {
        return $this->createElement('ImportNavTabsContainer')->isIsset();
    }

    /**
     * @param string $entity Entity class alias.
     * @param string $processorName export processor name.
     *
     * @return string Filepath to exported file.
     */
    private function performExport($entity, $processorName = null)
    {
        $entityClass = $this->aliasResolver->getClassByAlias($this->convertEntityNameToAlias($entity));

        if (!$processorName) {
            $processors = $this->processorRegistry->getProcessorAliasesByEntity('export', $entityClass);
            self::assertCount(1, $processors, sprintf(
                'Too many processors ("%s") for export "%s" entity',
                implode(', ', $processors),
                $entity
            ));
            $processorName = array_shift($processors);
        }

        $jobExecutor = $this->getContainer()->get('oro_importexport.job_executor');
        $filePath = FileManager::generateTmpFilePath(
            FileManager::generateFileName($processorName, 'csv')
        );

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

        return $filePath;
    }

    /**
     * @param Session $session
     *
     * @return ArrayCookieJar
     */
    private function getCookieJar(Session $session)
    {
        $cookies = $session->getDriver()->getWebDriverSession()->getCookie();
        $cookieJar = new ArrayCookieJar();
        foreach ($cookies as $cookie) {
            $cookieJar->add(new Cookie($cookie));
        }

        return $cookieJar;
    }
}
