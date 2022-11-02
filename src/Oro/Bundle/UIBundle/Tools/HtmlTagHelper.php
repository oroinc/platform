<?php

namespace Oro\Bundle\UIBundle\Tools;

use Oro\Bundle\FormBundle\Form\Converter\TagDefinitionConverter;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\TranslationBundle\Translation\TranslatorAwareInterface;
use Oro\Bundle\TranslationBundle\Translation\TranslatorAwareTrait;
use Oro\Bundle\UIBundle\Tools\HTMLPurifier\ErrorCollector;
use Oro\Bundle\UIBundle\Tools\HTMLPurifier\HTMLPurifier;
use Symfony\Component\Filesystem\Filesystem;

/**
 * This class helps format HTML
 */
class HtmlTagHelper implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    const SUB_DIR = 'ezyang';
    const MODE = 0775;

    const MAX_STRING_LENGTH = 256;

    const HTMLPURIFIER_CONFIG_REVISION = 2019072301;

    /**
     * @var HTMLPurifier[]
     */
    private $htmlPurifiers = [];

    /**
     * @var array
     */
    private $additionalAttributes = [];

    /**
     * @var array
     */
    private $additionalElements = [];

    /**
     * @var HtmlTagProvider
     */
    private $htmlTagProvider;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var ErrorCollector
     */
    private $lastErrorCollector;

    /**
     * @param HtmlTagProvider $htmlTagProvider
     * @param string|null $cacheDir
     */
    public function __construct(HtmlTagProvider $htmlTagProvider, $cacheDir = null)
    {
        $this->htmlTagProvider = $htmlTagProvider;
        $this->cacheDir = $cacheDir;
    }

    /**
     * @return ErrorCollector
     */
    public function getLastErrorCollector()
    {
        return $this->lastErrorCollector;
    }

    public function setAttribute(string $elementName, string $attributeName, string $attributeType): void
    {
        $this->additionalAttributes[$elementName][$attributeName] = $attributeType;
    }

    public function setElement(
        string $elementName,
        string $type,
        string $contents,
        string $attributeCollections,
        bool $excludeSameElement = false
    ): void {
        $this->additionalElements[$elementName] = [
            'type' => $type,
            'contents' => $contents,
            'attribute_collections' => $attributeCollections,
            'excludeSameElement' => $excludeSameElement
        ];
    }

    /**
     * @param string $value
     * @param string $scope
     * @param bool $collectErrors
     *
     * @return string
     */
    public function sanitize($value, string $scope = 'default', bool $collectErrors = true)
    {
        if (!$value) {
            $this->lastErrorCollector = null;

            return $value;
        }

        $purifier = $this->getPurifier($scope, $collectErrors);

        $result = $purifier->purify($value);
        if ($collectErrors) {
            $this->lastErrorCollector = $purifier->context->get('ErrorCollector', true);
        }

        return $result;
    }

    /**
     * Remove all html elements but leave new lines
     *
     * @param string $string
     * @return string
     */
    public function purify($string)
    {
        return trim($this->sanitize($string));
    }

    /**
     * Remove all html elements
     *
     * @param string $string
     * @param bool $uiAllowedTags
     * @return string
     */
    public function stripTags($string, $uiAllowedTags = false)
    {
        $string = str_replace('>', '> ', $string);

        if ($uiAllowedTags) {
            return strip_tags($string, $this->htmlTagProvider->getAllowedTags('default'));
        }

        $result = trim(strip_tags($string));

        return preg_replace('/\s+/u', ' ', $result);
    }

    /**
     * Shorten text
     *
     * @param string $string
     * @param int $maxLength
     * @return string
     */
    public function shorten($string, $maxLength = self::MAX_STRING_LENGTH)
    {
        $encoding = mb_detect_encoding($string);
        if (mb_strlen($string, $encoding) > $maxLength) {
            $string = mb_substr($string, 0, $maxLength, $encoding);
            $lastOccurrencePos = mb_strrpos($string, ' ', null, $encoding);
            if ($lastOccurrencePos !== false) {
                $string = mb_substr($string, 0, $lastOccurrencePos, $encoding);
            }
        }

        return trim($string);
    }

    /**
     * Filter HTML with HTMLPurifier, allow embedded tags
     *
     * @param string $string
     * @return string
     */
    public function escape($string)
    {
        $config = \HTMLPurifier_HTML5Config::createDefault();
        $this->fillCacheConfig($config, '_escape');
        $config->set('Attr.EnableID', true);
        $config->set('Core.EscapeInvalidTags', true);

        $purifier = new \HTMLPurifier($config);

        return $purifier->purify($string);
    }

    public function stripLongWords(string $string, int $maxLength = self::MAX_STRING_LENGTH): string
    {
        $words = preg_split('/\s+/', $string);

        $words = array_filter(
            $words,
            function ($item) use ($maxLength) {
                return \strlen($item) <= $maxLength;
            }
        );

        return implode(' ', $words);
    }

    /**
     * Configure cache
     */
    private function fillCacheConfig(\HTMLPurifier_Config $config, string $definitionId): void
    {
        if ($this->cacheDir) {
            $cacheDir = $this->cacheDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . self::SUB_DIR;
            $this->touchCacheDir($cacheDir);
            $config->set('HTML.DefinitionID', __CLASS__ . ':' . $definitionId);
            $config->set('HTML.DefinitionRev', self::HTMLPURIFIER_CONFIG_REVISION);
            $config->set('Cache.SerializerPath', $cacheDir);
            $config->set('Cache.SerializerPermissions', self::MODE);
        } else {
            $config->set('Cache.DefinitionImpl', null);
        }
    }

    /**
     * Configure allowed tags
     */
    private function fillAllowedElementsConfig(\HTMLPurifier_Config $config, string $scope): void
    {
        $converter = new TagDefinitionConverter();
        $allowedElements = implode(',', $this->htmlTagProvider->getAllowedElements($scope));
        if ($allowedElements) {
            $config->set('HTML.AllowedElements', $converter->getElements($allowedElements));
            $config->set('HTML.AllowedAttributes', $converter->getAttributes($allowedElements));
        } else {
            $config->set('HTML.Allowed', '');
        }
    }

    /**
     * Configure iframe tag allowance.
     */
    private function fillIframeSupportConfig(string $scope, \HTMLPurifier_Config $config): void
    {
        $allowedTags = $this->htmlTagProvider->getAllowedTags($scope);
        if (str_contains($allowedTags, '<iframe>')) {
            $config->set('HTML.SafeIframe', true);
            $config->set('URI.SafeIframeRegexp', $this->htmlTagProvider->getIframeRegexp($scope));
        }
    }

    /**
     * Create cache dir if it is needed
     *
     * @param string $cacheDir
     */
    private function touchCacheDir(string $cacheDir): void
    {
        $fs = new Filesystem();
        if (!$fs->exists($cacheDir)) {
            $fs->mkdir($cacheDir, self::MODE);
        }
    }

    private function getPurifier(string $scope, bool $collectErrors): HTMLPurifier
    {
        $key = $scope . '|' . ($collectErrors ? '1' : '0');
        if (!array_key_exists($key, $this->htmlPurifiers)) {
            $this->htmlPurifiers[$key] = $this->buildPurifier($scope, $collectErrors);
        }

        return $this->htmlPurifiers[$key];
    }

    private function buildPurifier(string $scope, bool $collectErrors): HTMLPurifier
    {
        $html5Config = \HTMLPurifier_HTML5Config::createDefault();
        $config = \HTMLPurifier_Config::create($html5Config);

        $config->set('Core.CollectErrors', $collectErrors);
        // Fixes losing line breaks and whitespace caused by the disabled Core.CollectErrors.
        $config->set('Core.MaintainLineNumbers', true);

        // Disabled `rel` attribute transformer.
        $config->set('HTML.TargetNoopener', false);
        $config->set('HTML.TargetNoreferrer', false);
        $config->set('Attr.AllowedRel', $this->htmlTagProvider->getAllowedRel($scope));

        // add inline data support
        $config->set('URI.AllowedSchemes', $this->htmlTagProvider->getUriSchemes($scope));
        $config->set('Attr.EnableID', true);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('Filter.ExtractStyleBlocks.TidyImpl', false);
        $config->set('CSS.AllowImportant', true);
        $config->set('CSS.AllowTricky', true);
        $config->set('CSS.Proprietary', true);
        $config->set('CSS.Trusted', true);

        $this->fillIframeSupportConfig($scope, $config);
        $this->fillAllowedElementsConfig($config, $scope);
        $this->fillCacheConfig($config, $scope);

        $def = $config->maybeGetRawHTMLDefinition();
        if ($def) {
            foreach ($this->additionalElements as $elementName => $data) {
                $element = $def->addElement(
                    $elementName,
                    $data['type'],
                    $data['contents'],
                    $data['attribute_collections']
                );

                if ($data['excludeSameElement'] === true) {
                    $element->excludes = [$elementName => true];
                }
            }

            foreach ($this->additionalAttributes as $elementName => $attributeData) {
                foreach ($attributeData as $attributeName => $attributeType) {
                    $def->addAttribute($elementName, $attributeName, $attributeType);
                }
            }
        }

        $purifier = new HTMLPurifier($config);
        $purifier->setTranslator($this->translator);

        return $purifier;
    }
}
