<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Parser;

use Michelf\MarkdownExtra;

use Symfony\Component\HttpKernel\Config\FileLocator;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class MarkdownApiDocParser
{
    /**
     * @var array
     *
     * [
     *  class name => [
     *      "actions" => [
     *          action name => action description,
     *          ...
     *      ],
     *      "fields" => [
     *          field name => [
     *              action name => field description,
     *              ...
     *          ],
     *          ...
     *      ],
     *      "filters" => [
     *          filter name => filter description,
     *          ...
     *      ],
     *      "subresources" => [
     *          sub-resource name => [
     *              action name => sub-resource description,
     *              ...
     *          ],
     *          ...
     *      ],
     *  ],
     *  ...
     * ]
     */
    protected $loadedData = [];

    /** @var FileLocator */
    protected $fileLocator;

    /** @var string[] */
    protected $parsedFiles = [];

    /**
     * @param FileLocator $fileLocator
     */
    public function __construct(FileLocator $fileLocator)
    {
        $this->fileLocator = $fileLocator;
    }

    /**
     * @param string $className
     * @param string $actionName
     *
     * @return string|null
     */
    public function getActionDocumentation($className, $actionName)
    {
        return $this->getDocumentation($className, ConfigUtil::ACTIONS, $actionName);
    }

    /**
     * @param string      $className
     * @param string      $fieldName
     * @param string|null $actionName
     *
     * @return string|null
     */
    public function getFieldDocumentation($className, $fieldName, $actionName = null)
    {
        return $this->getDocumentation($className, ConfigUtil::FIELDS, $fieldName, $actionName ?: 'common');
    }

    /**
     * @param string $className
     * @param string $filterName
     *
     * @return string|null
     */
    public function getFilterDocumentation($className, $filterName)
    {
        return $this->getDocumentation($className, ConfigUtil::FILTERS, $filterName);
    }

    /**
     * @param string $className
     * @param string $subresourceName
     * @param string $actionName
     *
     * @return string|null
     */
    public function getSubresourceDocumentation($className, $subresourceName, $actionName)
    {
        return $this->getDocumentation($className, ConfigUtil::SUBRESOURCES, $subresourceName, $actionName);
    }

    /**
     * @param mixed $resource
     *
     * @return bool TRUE if the given resource is supported; otherwise, FALSE.
     */
    public function parseDocumentationResource($resource)
    {
        if (!is_string($resource)) {
            // unsupported resource type
            return false;
        }

        $pos = strrpos($resource, '.md');
        if (false === $pos) {
            // unsupported resource
            return false;
        }

        $filePath = $this->fileLocator->locate(substr($resource, 0, $pos + 3));
        if (!isset($this->parsedFiles[$filePath])) {
            $this->parseDocumentation(file_get_contents($filePath));

            // store parsed documentations file paths to avoid unnecessary parsing
            $this->parsedFiles[$filePath] = true;
        }

        return true;
    }

    /**
     * @param string $fileContent
     */
    protected function parseDocumentation($fileContent)
    {
        $parser = new MarkdownExtra();
        $html = $parser->transform($fileContent);

        $doc = new \DOMDocument();
        // suppress warnings like "Document is empty"
        @$doc->loadHTML($html);

        $rootNodes = $doc->getElementsByTagName('body');
        if (0 === $rootNodes->length) {
            return;
        }
        $rootNodes = $rootNodes->item(0)->childNodes;

        $state = new MarkdownApiDocParserState();
        foreach ($rootNodes as $node) {
            if ($node instanceof \DOMElement) {
                if ('h1' === $node->tagName) {
                    $state->setClassName($node->nodeValue);
                } elseif ('h2' === $node->tagName && $state->hasClass()) {
                    $state->setSection(strtolower($node->nodeValue));
                } elseif ('h3' === $node->tagName && $state->hasSection()) {
                    $state->setElement(strtolower($node->nodeValue));
                    $section = $state->getSection();
                    $state->setHasSubElements(
                        ConfigUtil::FIELDS === $section || ConfigUtil::SUBRESOURCES === $section
                    );
                } elseif ($state->hasElement()) {
                    if ('h4' === $node->tagName && $state->hasSubElements()) {
                        $state->setSubElement(strtolower($node->nodeValue));
                    } else {
                        $this->saveElement($doc, $node, $state);
                    }
                }
            }
        }
        $this->normalizeLoadedData();
    }

    protected function normalizeLoadedData()
    {
        // strip whitespace from the beginning and end of descriptions
        array_walk_recursive($this->loadedData, function (&$element) {
            if (is_string($element) && $element) {
                $element = trim($element);
            }
        });
    }

    /**
     * @param \DOMDocument              $doc
     * @param \DOMNode                  $node
     * @param MarkdownApiDocParserState $state
     */
    protected function saveElement(\DOMDocument $doc, \DOMNode $node, MarkdownApiDocParserState $state)
    {
        $className = $state->getClassName();
        $section = $state->getSection();
        $element = $state->getElement();
        $subElement = $state->getSubElement();
        if (!$state->hasSubElements()) {
            if (!isset($this->loadedData[$className][$section][$element])) {
                $this->loadedData[$className][$section][$element] = '';
            }
            if (ConfigUtil::FILTERS === $section) {
                $this->loadedData[$className][$section][$element] .= $node->nodeValue;
            } else {
                $this->loadedData[$className][$section][$element] .= $doc->saveHTML($node);
            }
        } else {
            if (!isset($this->loadedData[$className][$section][$element])) {
                $this->loadedData[$className][$section][$element] = [];
            }
            if (ConfigUtil::FIELDS === $section) {
                $text = $doc->saveHTML($node);
                if (!$subElement) {
                    $subElement = 'common';
                }
                foreach (explode(',', $subElement) as $action) {
                    $action = trim($action);
                    if (!isset($this->loadedData[$className][$section][$element][$action])) {
                        $this->loadedData[$className][$section][$element][$action] = '';
                    }
                    $this->loadedData[$className][$section][$element][$action] .= $text;
                }
            } elseif ($subElement) {
                if (!isset($this->loadedData[$className][$section][$element][$subElement])) {
                    $this->loadedData[$className][$section][$element][$subElement] = '';
                }
                $this->loadedData[$className][$section][$element][$subElement] .= $doc->saveHTML($node);
            }
        }
    }

    /**
     * @param string      $className
     * @param string      $section
     * @param string      $element
     * @param string|null $subElement
     *
     * @return string|null
     */
    protected function getDocumentation($className, $section, $element, $subElement = null)
    {
        $result = null;
        if (isset($this->loadedData[$className])) {
            $classData = $this->loadedData[$className];
            if (isset($classData[$section])) {
                $sectionData = $classData[$section];
                $element = strtolower($element);
                if (isset($sectionData[$element])) {
                    $elementData = $sectionData[$element];
                    if (!is_array($elementData)) {
                        $result = $elementData;
                    } elseif ($subElement) {
                        $subElement = strtolower($subElement);
                        if (isset($elementData[$subElement])) {
                            $result = $elementData[$subElement];
                        }
                    }
                }
            }
        }

        return $result;
    }
}
