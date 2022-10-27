<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\Output\Printer;

use Behat\Testwork\Output\Exception\MissingExtensionException;
use Behat\Testwork\Output\Printer\Factory\FilesystemOutputFactory;
use Behat\Testwork\Output\Printer\StreamOutputPrinter;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A convenient wrapper around the ConsoleOutputPrinter to write valid JUnit
 * reports.
 */
final class JUnitOutputPrinter extends StreamOutputPrinter
{
    public const XML_VERSION  = '1.0';
    public const XML_ENCODING = 'UTF-8';

    private ?\DOMDocument $domDocument = null;
    private ?\DOMElement $currentTestsuite = null;
    private ?\DOMElement $currentTestcase = null;
    private ?\DOMElement $testSuites = null;

    private array $callstack = [];

    public function __construct(FilesystemOutputFactory $outputFactory)
    {
        parent::__construct($outputFactory);
    }

    private function addCall(string $method, array $arguments)
    {
        $this->callstack[] = ['exec'.ucfirst($method), $arguments];
    }

    private function isCallstackCallable(): bool
    {
        // not only 'createNewFile' method called
        if (\count($this->callstack) > 1) {
            return true;
        }

        return false;
    }

    public function createNewFile($name, array $testsuitesAttributes = [])
    {
        $this->addCall(__FUNCTION__, [$name, $testsuitesAttributes]);
    }

    /**
     * Creates a new JUnit file.
     *
     * The file will be initialized with an XML definition and the root element.
     *
     * @param string $name                 The filename (without extension) and default value of the name attribute
     * @param array  $testsuitesAttributes Attributes for the root element
     */
    private function execCreateNewFile($name, array $testsuitesAttributes = [])
    {
        // This requires the DOM extension to be enabled.
        if (!extension_loaded('dom')) {
            throw new MissingExtensionException('The PHP DOM extension is required to generate JUnit reports.');
        }

        $this->execSetFileName(strtolower(trim(preg_replace('/[^[:alnum:]_]+/', '_', $name), '_')));

        $this->domDocument = new \DOMDocument(self::XML_VERSION, self::XML_ENCODING);
        $this->domDocument->formatOutput = true;

        $this->testSuites = $this->domDocument->createElement('testsuites');
        $this->domDocument->appendChild($this->testSuites);
        $this->addAttributesToNode($this->testSuites, array_merge(array('name' => $name), $testsuitesAttributes));
        $this->execFlush();
    }

    public function addTestsuite(array $testsuiteAttributes = [])
    {
        $this->addCall(__FUNCTION__, [$testsuiteAttributes]);
    }

    /**
     * Adds a new <testsuite> node.
     */
    private function execAddTestsuite(array $testsuiteAttributes = [])
    {
        $this->currentTestsuite = $this->domDocument->createElement('testsuite');
        $this->testSuites->appendChild($this->currentTestsuite);
        $this->addAttributesToNode($this->currentTestsuite, $testsuiteAttributes);
    }

    public function addTestcase(array $testcaseAttributes = [])
    {
        $this->addCall(__FUNCTION__, [$testcaseAttributes]);
    }

    /**
     * Adds a new <testcase> node.
     */
    private function execAddTestcase(array $testcaseAttributes = [])
    {
        $this->currentTestcase = $this->domDocument->createElement('testcase');
        $this->currentTestsuite->appendChild($this->currentTestcase);
        $this->addAttributesToNode($this->currentTestcase, $testcaseAttributes);
    }

    public function addTestcaseChild($nodeName, array $nodeAttributes = [], $nodeValue = null)
    {
        $this->addCall(__FUNCTION__, [$nodeName, $nodeAttributes, $nodeValue]);
    }

    /**
     * Add a testcase child element.
     */
    private function execAddTestcaseChild($nodeName, array $nodeAttributes = [], $nodeValue = null)
    {
        $childNode = $this->domDocument->createElement($nodeName, $nodeValue ?? '');
        $this->currentTestcase->appendChild($childNode);
        $this->addAttributesToNode($childNode, $nodeAttributes);
    }

    private function addAttributesToNode(\DOMElement $node, array $attributes)
    {
        foreach ($attributes as $name => $value) {
            $node->setAttribute($name, $value);
        }
    }

    public function setFileName($fileName, $extension = 'xml')
    {
        $this->addCall(__FUNCTION__, [$fileName, $extension]);
    }

    /**
     * Sets file name.
     */
    private function execSetFileName($fileName, $extension = 'xml')
    {
        if ('.'.$extension !== substr($fileName, strlen($extension) + 1)) {
            $fileName .= '.'.$extension;
        }

        $this->getOutputFactory()->setFileName($fileName);
        $this->execFlush();
    }

    /**
     * Generate XML from the DOMDocument and parse to the writing stream
     */
    public function flush()
    {
        if ($this->isCallstackCallable()) {
            foreach ($this->callstack as $call) {
                [$method, $args] = $call;
                $this->$method(... $args);
            }
        }

        $this->callstack = [];

        $this->execFlush();
    }

    private function execFlush()
    {
        if ($this->domDocument instanceof \DOMDocument) {
            $this->getWritingStream()->write(
                $this->domDocument->saveXML(null, LIBXML_NOEMPTYTAG),
                false,
                OutputInterface::OUTPUT_RAW
            );
        }

        parent::flush();
    }
}
