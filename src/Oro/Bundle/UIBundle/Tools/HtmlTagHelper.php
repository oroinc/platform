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
     * @param bool   $collectErrors
     *
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function sanitize($value, string $scope = 'default', bool $collectErrors = true)
    {
        if (!$value) {
            $this->lastErrorCollector = null;

            return $value;
        }

        if (!array_key_exists($scope, $this->htmlPurifiers)) {
            $html5Config = \HTMLPurifier_HTML5Config::createDefault();
            $config = \HTMLPurifier_Config::create($html5Config);

            $config->set('Core.CollectErrors', $collectErrors);

            $config->set('HTML.DefinitionID', __CLASS__);
            $config->set('HTML.DefinitionRev', self::HTMLPURIFIER_CONFIG_REVISION);

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

            $allowedTags = $this->htmlTagProvider->getAllowedTags($scope);
            if (strpos($allowedTags, '<iframe>') !== false) {
                $config->set('HTML.SafeIframe', true);
                $config->set('URI.SafeIframeRegexp', $this->htmlTagProvider->getIframeRegexp($scope));
            }

            $this->fillAllowedElementsConfig($config, $scope);
            $this->fillCacheConfig($config);

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
            $this->htmlPurifiers[$scope] = $purifier;
        }

        $result = $this->htmlPurifiers[$scope]->purify($value);
        if ($collectErrors) {
            $this->lastErrorCollector = $this->htmlPurifiers[$scope]->context->get('ErrorCollector', true);
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
     * @param $string
     * @return string
     */
    public function escape($string)
    {
        $config = \HTMLPurifier_HTML5Config::createDefault();
        $config->set('Cache.SerializerPath', $this->cacheDir);
        $config->set('Cache.SerializerPermissions', 0775);
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
     *
     * @param \HTMLPurifier_Config $config
     */
    private function fillCacheConfig($config)
    {
        if ($this->cacheDir) {
            $cacheDir = $this->cacheDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . self::SUB_DIR;
            $this->touchCacheDir($cacheDir);
            $config->set('Cache.SerializerPath', $cacheDir);
            $config->set('Cache.SerializerPermissions', self::MODE);
        } else {
            $config->set('Cache.DefinitionImpl', null);
        }
    }

    /**
     * Configure allowed tags
     *
     * @param \HTMLPurifier_Config $config
     * @param string $scope
     */
    private function fillAllowedElementsConfig($config, $scope)
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
     * Create cache dir if need
     *
     * @param string $cacheDir
     */
    private function touchCacheDir($cacheDir)
    {
        $fs = new Filesystem();
        if (!$fs->exists($cacheDir)) {
            $fs->mkdir($cacheDir, self::MODE);
        }
    }
}
