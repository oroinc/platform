<?php

namespace Oro\Bundle\UIBundle\Tools;

use Oro\Bundle\FormBundle\Form\Converter\TagDefinitionConverter;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Symfony\Component\Filesystem\Filesystem;

/**
 * This class helps format HTML
 */
class HtmlTagHelper
{
    const SUB_DIR = 'ezyang';
    const MODE = 0775;

    const MAX_STRING_LENGTH = 256;

    const HTMLPURIFIER_CONFIG_REVISION = 2019072301;

    /**
     * @var \HtmlPurifier|null
     */
    private $htmlPurifier;

    /**
     * @var HtmlTagProvider
     */
    private $htmlTagProvider;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var string
     */
    private $scope;

    /**
     * @param HtmlTagProvider $htmlTagProvider
     * @param string|null $cacheDir
     * @param string $scope
     */
    public function __construct(
        HtmlTagProvider $htmlTagProvider,
        $cacheDir = null,
        $scope = 'default'
    ) {
        $this->htmlTagProvider = $htmlTagProvider;
        $this->cacheDir = $cacheDir;
        $this->scope = $scope;
    }

    /**
     * @param string $value
     * @param string $scope
     * @return string
     */
    public function sanitize($value, $scope = 'default')
    {
        if (!$value) {
            return $value;
        }

        if (!$this->htmlPurifier) {
            $html5Config = \HTMLPurifier_HTML5Config::createDefault();
            $config = \HTMLPurifier_Config::create($html5Config);

            $config->set('HTML.DefinitionID', __CLASS__);
            $config->set('HTML.DefinitionRev', self::HTMLPURIFIER_CONFIG_REVISION);

            // add inline data support
            $config->set('URI.AllowedSchemes', $this->htmlTagProvider->getUriSchemes($scope));
            $config->set('Attr.EnableID', true);
            $config->set('Attr.AllowedFrameTargets', ['_blank']);
            $config->set('HTML.SafeIframe', true);
            $config->set('URI.SafeIframeRegexp', $this->htmlTagProvider->getIframeRegexp($scope));
            $config->set('Filter.ExtractStyleBlocks.TidyImpl', false);
            $config->set('CSS.AllowImportant', true);
            $config->set('CSS.AllowTricky', true);
            $config->set('CSS.Proprietary', true);
            $config->set('CSS.Trusted', true);

            $this->fillAllowedElementsConfig($config, $scope);
            $this->fillCacheConfig($config);

            if ($def = $config->maybeGetRawHTMLDefinition()) {
                $def->addElement(
                    'style',
                    'Block',
                    'Flow',
                    'Common',
                    [
                        'type' => 'Enum#text/css',
                        'media' => 'CDATA',
                    ]
                );
            }

            $this->htmlPurifier = new \HTMLPurifier($config);
        }

        return $this->htmlPurifier->purify($value);
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

    /**
     * @param string $string
     * @param int $maxLength
     * @return string
     */
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
