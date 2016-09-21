<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Parser;

use Michelf\Markdown;

use Symfony\Component\HttpKernel\Config\FileLocator;

class ApiDocMdParser
{
    /**
     * @var array
     *
     * [OroCRM\Bundle\SalesBundle\Entity\Opportunity] => Array
     *  [<section name>] =>
     *      [<element name>] => '<element description>'
     *  ...
     *  [actions] => Array
     *      [GET_LIST] => 'action GET_LIST description'
     *      ...
     *      [CREATE]   => 'action GET description'
     *  [fields] => Array
     *      [field1]  => 'field 1 description'
     *      ...
     *      [field_N] => 'field N description'
     */
    public $loadedDocumentation = [];

    /** @var FileLocator */
    protected $fileLocator;

    /** @var [string] */
    protected $parsedDocumentation = [];

    /**
     * @param FileLocator $fileLocator
     */
    public function __construct(FileLocator $fileLocator)
    {
        $this->fileLocator = $fileLocator;
    }

    /**
     * @param string $className
     * @param string $section
     * @param string $element
     * @param string $resource
     *
     * @return mixed|string
     */
    public function getDocumentation($className, $section, $element = null, $resource = null)
    {
        if (null !== $resource) {
            $this->parseDocumentationResource($resource);
        }

        if (array_key_exists($className, $this->loadedDocumentation)) {
            $classDocumentation = $this->loadedDocumentation[$className];
            if (array_key_exists($section, $classDocumentation)) {
                $sectionDocumentation = $classDocumentation[$section];
                if (!is_array($sectionDocumentation)) {
                    return $sectionDocumentation;
                }

                if (null !== $element && array_key_exists($element, $sectionDocumentation)) {
                    return $sectionDocumentation[$element];
                }
            }
        }

        return '';
    }

    /**
     * @param string $resourceLink
     */
    public function parseDocumentationResource($resourceLink)
    {
        $fileContent = $this->getFileContent($resourceLink);
        if (!$fileContent) {
            return;
        }

        $parser = new Markdown();
        $html = $parser->transform($fileContent);

        $doc = new \DOMDocument();
        $doc->loadHTML($html);

        $xpath = new \DOMXPath($doc);

        $headers = $xpath->query('*//h1');
        foreach ($headers as $index => $header) {
            if (!isset($this->loadedDocumentation[$header->nodeValue])) {
                $this->loadedDocumentation[$header->nodeValue] = [];
            }
        }

        $headerKeys = array_keys($this->loadedDocumentation);
        foreach ($headerKeys as $index => $headerKey) {
            $type = ''; //'fields', 'filters', 'sorters', etc.
            $element = ''; //field name, filter name, etc.

            $subElements = $xpath->query("//*[preceding-sibling::h1[1][normalize-space()='{$headerKey}']]");
            foreach ($subElements as $subElement) {
                if ($subElement->tagName === 'h1') {
                    continue;
                }
                if ($subElement->tagName === 'h2') {
                    $type = strtolower($subElement->nodeValue);
                    if (!isset($this->loadedDocumentation[$headerKeys[$index]][$type])) {
                        $this->loadedDocumentation[$headerKeys[$index]][$type] = [];
                    }
                    continue;
                }
                if ($subElement->tagName === 'h3') {
                    $element = $subElement->nodeValue;
                    $this->loadedDocumentation[$headerKeys[$index]][$type][$element] = '';
                    continue;
                }

                if ('filters' === $type) {
                    $this->loadedDocumentation[$headerKeys[$index]][$type][$element] .= $subElement->nodeValue;
                } else {
                    $this->loadedDocumentation[$headerKeys[$index]][$type][$element] .= $doc->saveHTML($subElement);
                }
            }
        }
    }

    /**
     * @param $resourceLink
     *
     * @return string|bool
     */
    protected function getFileContent($resourceLink)
    {
        $pos = strrpos($resourceLink, '.md');
        if (false === $pos) {
            return false;
        }

        $filePath = $this->fileLocator->locate(substr($resourceLink, 0, $pos + 3));
        if (isset($this->parsedDocumentation[$filePath])) {
            return false;
        }

        //store parsed documentations file paths to avoid unnecessary parsing
        $this->parsedDocumentation[$filePath] = true;

        return file_get_contents($filePath);
    }
}
