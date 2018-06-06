<?php

namespace Oro\Bundle\UIBundle\Tools;

use Oro\Bundle\FormBundle\Form\DataTransformer\SanitizeHTMLTransformer;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;

/**
 * This class helps format HTML
 */
class HtmlTagHelper
{
    const MAX_STRING_LENGTH = 256;

    /** @var HtmlTagProvider */
    protected $htmlTagProvider;

    /** @var string */
    protected $cacheDir;

    /** @var SanitizeHTMLTransformer|null */
    protected $purifyTransformer;

    /**
     * @param HtmlTagProvider $htmlTagProvider
     * @param string|null $cacheDir
     */
    public function __construct(
        HtmlTagProvider $htmlTagProvider,
        $cacheDir = null
    ) {
        $this->htmlTagProvider = $htmlTagProvider;
        $this->cacheDir = $cacheDir;
    }

    /**
     * Remove html elements except allowed
     *
     * @param string $string
     *
     * @return string
     */
    public function sanitize($string)
    {
        $transformer = new SanitizeHTMLTransformer(
            implode(',', $this->htmlTagProvider->getAllowedElements()),
            $this->cacheDir
        );

        return $transformer->transform($string);
    }

    /**
     * Remove all html elements but leave new lines
     *
     * @param string $string
     * @return string
     */
    public function purify($string)
    {
        if (!$this->purifyTransformer) {
            $this->purifyTransformer = new SanitizeHTMLTransformer(null, $this->cacheDir);
        }

        return trim($this->purifyTransformer->transform($string));
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
            return strip_tags($string, $this->htmlTagProvider->getAllowedTags());
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
        $config = \HTMLPurifier_Config::createDefault();
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
}
