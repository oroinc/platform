<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Parser;

use Michelf\Markdown;

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
     * @param string $className
     * @param string $fieldName
     * @param string $actionName
     *
     * @return string|null
     */
    public function getFieldDocumentation($className, $fieldName, $actionName)
    {
        return $this->getDocumentation($className, ConfigUtil::FIELDS, $fieldName, $actionName);
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
        $parser = new Markdown();
        $html = $parser->transform($fileContent);

        $doc = new \DOMDocument();
        $doc->loadHTML($html);

        $xpath = new \DOMXPath($doc);

        $headers = $xpath->query('*//h1');
        foreach ($headers as $index => $header) {
            if (!isset($this->loadedData[$header->nodeValue])) {
                $this->loadedData[$header->nodeValue] = [];
            }
        }

        $classNames = array_keys($this->loadedData);
        foreach ($classNames as $className) {
            $section = ''; // 'fields', 'filters', 'actions', etc.
            $element = ''; // field name, filter name, etc.
            $action = '';

            $nodes = $xpath->query("//*[preceding-sibling::h1[1][normalize-space()='{$className}']]");
            foreach ($nodes as $node) {
                /** @var \DOMElement $node */

                if (in_array($node->tagName, ['h1', 'h2', 'h3', 'h4'], true)) {
                    list($section, $element, $action) = $this->parseDocumentationHeaders(
                        $node,
                        $className,
                        $section,
                        $element
                    );
                    continue;
                }

                switch ($section) {
                    case ConfigUtil::ACTIONS:
                        $this->loadedData[$className][$section][$element] .= $doc->saveHTML($node);
                        break;
                    case ConfigUtil::FIELDS:
                        $actions = $action ?: 'common';
                        $text = $doc->saveHTML($node);
                        foreach (explode(',', $actions) as $actionName) {
                            $actionName = trim($actionName);
                            if (!array_key_exists($actionName, $this->loadedData[$className][$section][$element])) {
                                $this->loadedData[$className][$section][$element][$actionName] = '';
                            }
                            $this->loadedData[$className][$section][$element][$actionName] .= $text;
                        }
                        break;
                    case ConfigUtil::SUBRESOURCES:
                        if (!array_key_exists($action, $this->loadedData[$className][$section][$element])) {
                            $this->loadedData[$className][$section][$element][$action] = '';
                        }
                        $this->loadedData[$className][$section][$element][$action] .= $doc->saveHTML($node);
                        break;
                    case ConfigUtil::FILTERS:
                        $this->loadedData[$className][$section][$element] .= $node->nodeValue;
                        break;
                    default:
                        throw new \RuntimeException(sprintf('Unknown section: "%s".', $section));
                }
            }
        }
    }

    /**
     * @param \DOMElement $tag
     * @param string      $className
     * @param string      $section
     * @param string      $element
     *
     * @return array
     */
    protected function parseDocumentationHeaders($tag, $className, $section, $element)
    {
        $action = '';

        if ($tag->tagName === 'h2') {
            $section = strtolower($tag->nodeValue);
            if (!isset($this->loadedData[$className][$section])) {
                $this->loadedData[$className][$section] = [];
            }
        }

        if ($tag->tagName === 'h3') {
            $element = strtolower($tag->nodeValue);
            $hasSubElements = (ConfigUtil::FIELDS === $section || ConfigUtil::SUBRESOURCES === $section);
            $this->loadedData[$className][$section][$element] = $hasSubElements ? [] : '';
        }

        if ($tag->tagName === 'h4') {
            $action = strtolower($tag->nodeValue);
        }

        return [$section, $element, $action];
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
        if (array_key_exists($className, $this->loadedData)) {
            $classDocumentation = $this->loadedData[$className];
            if (array_key_exists($section, $classDocumentation)) {
                $sectionDocumentation = $classDocumentation[$section];
                $element = strtolower($element);
                if (array_key_exists($element, $sectionDocumentation)) {
                    $elementDocumentation = $sectionDocumentation[$element];
                    if (!is_array($elementDocumentation)) {
                        return $elementDocumentation;
                    }
                    if ($subElement) {
                        $subElement = strtolower($subElement);
                        if (!array_key_exists($subElement, $elementDocumentation)
                            && array_key_exists('common', $elementDocumentation)
                        ) {
                            return $elementDocumentation['common'];
                        }
                        if (array_key_exists($subElement, $elementDocumentation)) {
                            return $elementDocumentation[$subElement];
                        }
                    }
                }
            }
        }

        return null;
    }
}
