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
     *  [filters] => Array
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
     * @param string $actionName
     * @param string $resource
     *
     * @return mixed|string
     */
    public function getDocumentation($className, $section, $element = null, $actionName = null, $resource = null)
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

                if (null !== $element) {
                    $element = strtolower($element);
                    $actionName = strtolower($actionName);
                    if (array_key_exists($element, $sectionDocumentation)) {
                        $elementDocumentation = $sectionDocumentation[$element];
                        if (!is_array($elementDocumentation)) {
                            return $elementDocumentation;
                        }
                        if (null !== $actionName) {
                            if (!array_key_exists($actionName, $elementDocumentation)
                                && array_key_exists('common', $elementDocumentation)) {
                                return $elementDocumentation['common'];
                            }
                            if (array_key_exists($actionName, $elementDocumentation)) {
                                return $elementDocumentation[$actionName];
                            }

                        }
                    }
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
            $type = ''; //'fields', 'filters', 'actions', etc.
            $element = ''; //field name, filter name, etc.
            $action = '';

            $subElements = $xpath->query("//*[preceding-sibling::h1[1][normalize-space()='{$headerKey}']]");
            foreach ($subElements as $subElement) {
                if ($subElement->tagName === 'h1') {
                    $action = '';
                    continue;
                }
                if ($subElement->tagName === 'h2') {
                    $type = strtolower($subElement->nodeValue);
                    if (!isset($this->loadedDocumentation[$headerKeys[$index]][$type])) {
                        $this->loadedDocumentation[$headerKeys[$index]][$type] = [];
                    }
                    $action = '';
                    continue;
                }
                if ($subElement->tagName === 'h3') {
                    $element = strtolower($subElement->nodeValue);
                    $this->loadedDocumentation[$headerKeys[$index]][$type][$element] = $type === 'fields' ? [] : '';
                    $action = '';
                    continue;
                }

                if ('filters' === $type) {
                    $this->loadedDocumentation[$headerKeys[$index]][$type][$element] .= $subElement->nodeValue;
                } elseif ('actions' !== $type) {
                    if ($subElement->tagName === 'h4') {
                        $action = strtolower($subElement->nodeValue);
                        continue;
                    }
                    $actionName = 'common';
                    if ($action) {
                        $actionName = $action;
                    }
                    if (!array_key_exists(
                        $actionName,
                        $this->loadedDocumentation[$headerKeys[$index]][$type][$element]
                    )) {
                        $this->loadedDocumentation[$headerKeys[$index]][$type][$element][$actionName] = '';
                    }

                    $this->loadedDocumentation[$headerKeys[$index]][$type][$element][$actionName] .= $doc
                        ->saveHTML($subElement);
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
