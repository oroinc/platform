<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Parser;

use Michelf\MarkdownExtra;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\InheritDocUtil;
use Symfony\Component\HttpKernel\Config\FileLocator;

/**
 * Extracts Data API resources documentation from Markdown files.
 * This parser supports Markdown Extra syntax.
 * @link https://michelf.ca/projects/php-markdown/extra/
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class MarkdownApiDocParser implements ResourceDocParserInterface
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
    private $loadedData = [];

    /** @var FileLocator */
    private $fileLocator;

    /** @var string[] */
    private $parsedFiles = [];

    /**
     * @param FileLocator $fileLocator
     */
    public function __construct(FileLocator $fileLocator)
    {
        $this->fileLocator = $fileLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function getActionDocumentation(string $className, string $actionName): ?string
    {
        return $this->getDocumentation($className, ConfigUtil::ACTIONS, $actionName);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDocumentation(
        string $className,
        string $fieldName,
        ?string $actionName = null
    ): ?string {
        return $this->getDocumentation($className, ConfigUtil::FIELDS, $fieldName, $actionName ?: 'common');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterDocumentation(string $className, string $filterName): ?string
    {
        return $this->getDocumentation($className, ConfigUtil::FILTERS, $filterName);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubresourceDocumentation(
        string $className,
        string $subresourceName,
        string $actionName
    ): ?string {
        return $this->getDocumentation($className, ConfigUtil::SUBRESOURCES, $subresourceName, $actionName);
    }

    /**
     * {@inheritdoc}
     */
    public function registerDocumentationResource(string $resource): bool
    {
        $pos = \strrpos($resource, '.md');
        if (false === $pos) {
            // unsupported resource
            return false;
        }

        /** @var string $filePath */
        $filePath = $this->fileLocator->locate(\substr($resource, 0, $pos + 3));
        if (!isset($this->parsedFiles[$filePath])) {
            $existingData = $this->loadedData;
            $this->loadedData = [];
            $this->parseDocumentation(\file_get_contents($filePath));
            if (!empty($existingData)) {
                $newData = $this->loadedData;
                $this->loadedData = $existingData;
                if (!empty($newData)) {
                    $this->merge($newData);
                }
            }

            // store parsed documentations file paths to avoid unnecessary parsing
            $this->parsedFiles[$filePath] = true;
        }

        return true;
    }

    /**
     * @param array $newData
     */
    private function merge(array $newData): void
    {
        foreach ($newData as $className => $classData) {
            foreach ($classData as $section => $sectionData) {
                foreach ($sectionData as $element => $elementData) {
                    if (!$this->hasSubElements($section)) {
                        if (isset($this->loadedData[$className][$section][$element])
                            && InheritDocUtil::hasInheritDoc($elementData)
                        ) {
                            $elementData = InheritDocUtil::replaceInheritDoc(
                                $elementData,
                                $this->loadedData[$className][$section][$element]
                            );
                        }
                        $this->loadedData[$className][$section][$element] = $elementData;
                    } else {
                        foreach ($elementData as $subElement => $subElementData) {
                            if (isset($this->loadedData[$className][$section][$element][$subElement])
                                && InheritDocUtil::hasInheritDoc($subElementData)
                            ) {
                                $subElementData = InheritDocUtil::replaceInheritDoc(
                                    $subElementData,
                                    $this->loadedData[$className][$section][$element][$subElement]
                                );
                            }
                            $this->loadedData[$className][$section][$element][$subElement] = $subElementData;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $fileContent
     */
    private function parseDocumentation(string $fileContent): void
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
                    $state->setSection(\strtolower($node->nodeValue));
                } elseif ('h3' === $node->tagName && $state->hasSection()) {
                    $state->setElement(\strtolower($node->nodeValue));
                    $section = $state->getSection();
                    $state->setHasSubElements($this->hasSubElements($section));
                } elseif ($state->hasElement()) {
                    if ('h4' === $node->tagName && $state->hasSubElements()) {
                        $state->setSubElement(\strtolower($node->nodeValue));
                    } else {
                        $this->saveElement($doc, $node, $state);
                    }
                }
            }
        }
        $this->normalizeLoadedData();
    }

    /**
     * @param string $section
     *
     * @return bool
     */
    private function hasSubElements(string $section): bool
    {
        return ConfigUtil::FIELDS === $section || ConfigUtil::SUBRESOURCES === $section;
    }

    private function normalizeLoadedData(): void
    {
        // strip whitespace from the beginning and end of descriptions
        \array_walk_recursive($this->loadedData, function (&$element) {
            if (\is_string($element) && $element) {
                $element = trim($element);
            }
        });
    }

    /**
     * @param \DOMDocument              $doc
     * @param \DOMNode                  $node
     * @param MarkdownApiDocParserState $state
     */
    private function saveElement(\DOMDocument $doc, \DOMNode $node, MarkdownApiDocParserState $state): void
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
                foreach (\explode(',', $subElement) as $action) {
                    $action = \trim($action);
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
    private function getDocumentation(
        string $className,
        string $section,
        string $element,
        ?string $subElement = null
    ): ?string {
        $result = null;
        if (isset($this->loadedData[$className])) {
            $classData = $this->loadedData[$className];
            if (isset($classData[$section])) {
                $sectionData = $classData[$section];
                $element = \strtolower($element);
                if (isset($sectionData[$element])) {
                    $elementData = $sectionData[$element];
                    if (!\is_array($elementData)) {
                        $result = $elementData;
                    } elseif ($subElement) {
                        $subElement = \strtolower($subElement);
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
