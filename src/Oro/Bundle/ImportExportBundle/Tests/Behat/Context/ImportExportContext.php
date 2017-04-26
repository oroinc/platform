<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Doctrine\Common\Inflector\Inflector;
use Guzzle\Http\Client;
use Guzzle\Plugin\Cookie\Cookie;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
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
     * @param EntityAliasResolver $aliasResolver
     * @param ProcessorRegistry $processorRegistry
     */
    public function __construct(EntityAliasResolver $aliasResolver, ProcessorRegistry $processorRegistry)
    {
        $this->aliasResolver = $aliasResolver;
        $this->processorRegistry = $processorRegistry;
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
        $this->template = tempnam(sys_get_temp_dir(), 'opportunity_template_');

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
        $this->importFile = tempnam(sys_get_temp_dir(), 'opportunity_import_data_');
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
        // todo: CRM-7599 Replace sleep to appropriate logic
        sleep(5);
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
}
