<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Features\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Doctrine\DBAL\DriverManager;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\ServiceContainer\BehatStatisticExtension;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class StatisticContext implements Context
{
    /**
     * @var string
     */
    private $phpBin;

    /**
     * @var Process
     */
    private $process;

    /**
     * @var string
     */
    private $testAppPath;

    /**
     * @var array
     */
    private $dbConfig;

    /**
     * Prepares test folders in the temporary directory.
     *
     * @BeforeScenario
     */
    public function prepareProcess()
    {
        $this->testAppPath = realpath(__DIR__.'/../../TestApp');
        $phpFinder = new PhpExecutableFinder();
        if (false === $php = $phpFinder->find()) {
            throw new \RuntimeException('Unable to find the PHP executable.');
        }
        $this->phpBin = $php;
        $this->process = new Process(null);
    }

    /** @BeforeScenario */
    public function prepareBehatYaml()
    {
        $behatYaml = $this->testAppPath.'/behat.yml';

        if (file_exists($behatYaml)) {
            unlink($behatYaml);
        }

        copy($this->testAppPath.'/behat.yml.dist', $behatYaml);
    }

    /**
     * Runs behat command with provided parameters
     *
     * @When /^(?:|I )run "behat(?: ((?:\"|[^"])*))?"$/
     *
     * @param   string $argumentsString
     */
    public function iRunBehat($argumentsString = '')
    {
        $argumentsString = strtr($argumentsString, ['\'' => '"']);

        $this->process->setWorkingDirectory($this->testAppPath);
        $this->process->setCommandLine(
            sprintf(
                '%s %s %s %s -c %s',
                $this->phpBin,
                escapeshellarg(BEHAT_BIN_PATH),
                $argumentsString,
                strtr('--format-settings=\'{"timer": false}\'', ['\'' => '"', '"' => '\"']),
                $this->testAppPath.'/behat.yml'
            )
        );
        $this->process->start();
        $this->process->wait();
    }

    /**
     * @Given enabled Extension in behat.yml:
     */
    public function enabledExtensionInBehatYml(PyStringNode $extensionConfig)
    {
        $config = Yaml::parse(file_get_contents($this->testAppPath.'/behat.yml'));
        $extensionConfig = Yaml::parse($extensionConfig->getRaw());
        $config['default']['extensions'] = $extensionConfig;
        $this->dbConfig = array_shift($extensionConfig)['connection'];

        file_put_contents($this->testAppPath.'/behat.yml', Yaml::dump($config, 6));
    }

    /**
     * @Given enabled suites in behat.yml:
     */
    public function enabledSuitesInBehatYml(PyStringNode $suitesConfig)
    {
        $config = Yaml::parse(file_get_contents($this->testAppPath.'/behat.yml'));
        $suitesConfig = Yaml::parse($suitesConfig->getRaw());
        $config['default']['suites'] = $suitesConfig;

        file_put_contents($this->testAppPath.'/behat.yml', Yaml::dump($config, 6));
    }

    /**
     * @Given environment variables:
     */
    public function environmentVariables(TableNode $table)
    {
        $rows = $table->getRows();
        foreach ($rows as $row) {
            list($env, $value) = $row;
            putenv("$env=$value");
        }
    }
    /**
     * Checks whether previously runned command passes|failes with provided output.
     *
     * @Then /^it should (fail|pass) with:$/
     *
     * @param   string       $success "fail" or "pass"
     * @param   PyStringNode $text    PyString text instance
     */
    public function itShouldPassWith($success, PyStringNode $text)
    {
        $this->itShouldFail($success);
        $this->theOutputShouldContain($text);
    }

    /**
     * Checks whether previously runned command passes|failes without provided output.
     *
     * @Then /^it should (fail|pass) without:$/
     *
     * @param   string       $success "fail" or "pass"
     * @param   PyStringNode $text    PyString text instance
     */
    public function itShouldPassWithout($success, PyStringNode $text)
    {
        $this->itShouldFail($success);
        $this->theOutputShouldNotContain($text);
    }

    /**
     * Checks whether previously runned command failed|passed.
     *
     * @Then /^it should (fail|pass)$/
     *
     * @param   string $success "fail" or "pass"
     */
    public function itShouldFail($success)
    {
        if ('fail' === $success) {
            if (0 === $this->getExitCode()) {
                echo 'Actual output:' . PHP_EOL . PHP_EOL . $this->getOutput();
            }

            \PHPUnit\Framework\Assert::assertNotEquals(0, $this->getExitCode());
        } else {
            if (0 !== $this->getExitCode()) {
                echo 'Actual output:' . PHP_EOL . PHP_EOL . $this->getOutput();
            }

            \PHPUnit\Framework\Assert::assertEquals(0, $this->getExitCode());
        }
    }

    /**
     * Checks whether last command output contains provided string.
     *
     * @Then the output should contain:
     *
     * @param   PyStringNode $text PyString text instance
     */
    public function theOutputShouldContain(PyStringNode $text)
    {
        \PHPUnit\Framework\Assert::assertContains($this->getExpectedOutput($text), $this->getOutput());
    }

    /**
     * Checks whether last command output not contains provided string.
     *
     * @Then the output should not contain:
     *
     * @param   PyStringNode $text PyString text instance
     */
    public function theOutputShouldNotContain(PyStringNode $text)
    {
        \PHPUnit\Framework\Assert::assertNotContains($this->getExpectedOutput($text), $this->getOutput());
    }

    /**
     * @Given :dbname sqlite database exists
     */
    public function sqliteDatabaseExists($dbname)
    {
        $dbFile = $this->testAppPath.'/'.$dbname;

        if (file_exists($dbFile)) {
            unlink($dbFile);
        }

        new \SQLite3($dbFile);
        $this->dbConfig['path'] = $dbFile;
    }

    /**
     * @Given /^(mysql|pgsql) database exists$/
     */
    public function databaseExists()
    {
        if (!isset($this->dbConfig['dbname'])) {
            throw new RuntimeException('No db name in configuration');
        }

        $dbName =  $this->dbConfig['dbname'];
        $dbConfig = $this->dbConfig;
        $dbConfig['dbname'] = null;

        $conn = DriverManager::getConnection($dbConfig);
        $sm = $conn->getSchemaManager();

        if (in_array($dbName, $sm->listDatabases())) {
            $sm->dropDatabase($dbName);
        }

        $conn->getSchemaManager()->createDatabase($dbName);
    }

    /**
     * @Then :tableName table should contains records:
     */
    public function iDbShouldContainsRecords($tableName, TableNode $table)
    {
        $conn = DriverManager::getConnection($this->dbConfig);
        $queryBuilder = $conn->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from($tableName)
        ;
        $result = $queryBuilder->execute()->fetchAll();

        \PHPUnit\Framework\Assert::assertEquals($table->getHash(), $result);
    }

    /**
     * @return int|null
     */
    private function getExitCode()
    {
        return $this->process->getExitCode();
    }

    /**
     * @return string
     */
    private function getOutput()
    {
        $output = $this->process->getErrorOutput() . $this->process->getOutput();

        // Normalize the line endings in the output
        if ("\n" !== PHP_EOL) {
            $output = str_replace(PHP_EOL, "\n", $output);
        }

        return trim(preg_replace("/ +$/m", '', $output));
    }

    /**
     * @param PyStringNode $expectedText
     * @return mixed|string
     */
    private function getExpectedOutput(PyStringNode $expectedText)
    {
        $text = strtr($expectedText, ['\'\'\'' => '"""']);

        // windows path fix
        if ('/' !== DIRECTORY_SEPARATOR) {
            $text = preg_replace_callback(
                '/ features\/[^\n ]+/',
                function ($matches) {
                    return str_replace('/', DIRECTORY_SEPARATOR, $matches[0]);
                },
                $text
            );
            $text = preg_replace_callback(
                '/\<span class\="path"\>features\/[^\<]+/',
                function ($matches) {
                    return str_replace('/', DIRECTORY_SEPARATOR, $matches[0]);
                },
                $text
            );
            $text = preg_replace_callback(
                '/\+[fd] [^ ]+/',
                function ($matches) {
                    return str_replace('/', DIRECTORY_SEPARATOR, $matches[0]);
                },
                $text
            );
        }

        return $text;
    }

    /**
     * @Given table :tableName has data:
     */
    public function tableHasData($tableName, TableNode $dataTable)
    {
        $conn = DriverManager::getConnection($this->dbConfig);
        list($headers, $data) = [$dataTable->getRow(0), $dataTable->getHash()];

        do {
            $row = array_shift($data);
            $conn->insert($tableName, array_combine($headers, $row));
        } while (!empty($data));
    }

    /**
     * @Given /^(?:|I )reconfigure (StatisticExtension):$/
     */
    public function iReconfigureStatisticExtension(PyStringNode $extensionConfig)
    {
        $config = Yaml::parse(file_get_contents($this->testAppPath.'/behat.yml'));
        $extensionConfig = Yaml::parse($extensionConfig->getRaw());
        $config['default']['extensions'][BehatStatisticExtension::class] = $extensionConfig;

        file_put_contents($this->testAppPath.'/behat.yml', Yaml::dump($config, 6));
    }
}
