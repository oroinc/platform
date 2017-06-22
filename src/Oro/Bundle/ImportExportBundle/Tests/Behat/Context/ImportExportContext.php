<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
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
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class ImportExportContext extends OroFeatureContext implements KernelAwareContext, OroPageObjectAware
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
     * Download data template from entity grid page
     *
     * @When /^(?:|I )download "(?P<entity>([\w\s]+))" Data Template file$/
     */
    public function iDownloadDataTemplateFile($entity)
    {
        $entityClass = $this->aliasResolver->getClassByAlias($this->convertEntityNameToAlias($entity));
        $processors = $this->processorRegistry->getProcessorAliasesByEntity('export', $entityClass);

        self::assertCount(1, $processors, sprintf(
            'Too many processors ("%s") for export "%s" entity',
            implode(', ', $processors),
            $entity
        ));

        $importButton = $this->getSession()
            ->getPage()
            ->findLink('Import');
        self::assertNotNull($importButton);

        $importButton
            ->getParent()
            ->find('css', 'a.dropdown-toggle')
            ->click();
        $link = $importButton->getParent()->findLink('Download Data Template');

        self::assertNotNull($link);

        $url = $this->locatePath($this->getContainer()->get('router')->generate(
            'oro_importexport_export_template',
            ['processorAlias' => array_shift($processors)]
        ));
        $this->template = tempnam(
            $this->getKernel()->getRootDir().DIRECTORY_SEPARATOR.'import_export',
            'import_template_'
        );

        $cookies = $this->getSession()->getDriver()->getWebDriverSession()->getCookie()[0];
        $cookie = new Cookie();
        $cookie->setName($cookies['name']);
        $cookie->setValue($cookies['value']);
        $cookie->setDomain($cookies['domain']);

        $jar = new ArrayCookieJar();
        $jar->add($cookie);

        $client = new Client($this->getSession()->getCurrentUrl());
        $client->addSubscriber(new CookiePlugin($jar));
        $request = $client->get($url, null, ['save_to' => $this->template]);
        $response = $request->send();

        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * This method strictly compares data from the downloaded file
     *
     * @Given /^Exported file for "(?P<entity>([\w\s]+))" contains the following data:$/
     *
     * @param string    $entity
     * @param TableNode $expectedEntities
     */
    public function exportedFileContainsFollowingData($entity, TableNode $expectedEntities)
    {
        $filePath = $this->performExport($entity);

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
        $filePath = $this->performExport($entity);

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
    public function iDonTSeeBbCustomerNameColumn($column)
    {
        $csv = array_map('str_getcsv', file($this->template));
        self::assertNotContains($column, $csv[0]);
    }

    /**
     * Assert that given column is present on downloaded csv template
     * Example: When I download Data Template file
     *          And I see Account Customer name column
     *
     * @Then /^(?:|I )see (?P<column>([\w\s]+)) column$/
     */
    public function iSeeAccountColumn($column)
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
            $this->getKernel()->getRootDir().DIRECTORY_SEPARATOR.'import_export',
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

        $flashMessage = 'Import started successfully. You will receive email notification upon completion.';
        $this->oroMainContext->iShouldSeeFlashMessage($flashMessage);
        // todo: CRM-7599 Replace sleep to appropriate logic
        sleep(2);
    }

    /**
     * Expect that it will show errors
     *
     * @When /^(?:|I )try import file$/
     */
    public function tryImportFile()
    {
        $page = $this->getSession()->getPage();
        $page->clickLink('Import file');
        $this->waitForAjax();
        $this->createElement('ImportFileField')->attachFile($this->importFile);
        $page->pressButton('Submit');
        $this->waitForAjax();
    }

    /**
     * @When /^I import exported file$/
     */
    public function iImportExportedFile()
    {
        // todo: CRM-7599 Replace sleep to appropriate logic
        sleep(2);

        // @todo replace with fetching file path from email: CRM-7599
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
        $path = $this->getContainer()->getParameter('kernel.root_dir') . DIRECTORY_SEPARATOR . 'import_export';
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
     * @param string $entity Entity class alias.
     *
     * @return string Filepath to exported file.
     */
    private function performExport($entity)
    {
        $entityClass = $this->aliasResolver->getClassByAlias($this->convertEntityNameToAlias($entity));
        $processors = $this->processorRegistry->getProcessorAliasesByEntity('export', $entityClass);

        self::assertCount(1, $processors, sprintf(
            'Too many processors ("%s") for export "%s" entity',
            implode(', ', $processors),
            $entity
        ));

        $processorName = array_shift($processors);
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
}
