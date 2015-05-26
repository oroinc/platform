<?php

namespace Oro\Bundle\UIBundle\Tools;

use Oro\Bundle\FormBundle\Form\DataTransformer\SanitizeHTMLTransformer;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;

class HtmlTagHelper
{
    const MAX_STRING_LENGTH = 500;

    /** @var string */
    protected $cacheDir;

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
     * @param string $string
     * @return string
     */
    public function getPurify($string)
    {
        $transformer = new SanitizeHTMLTransformer(null, $this->cacheDir);
        return $transformer->transform($string);
    }

    /**
     * @param string $string
     * @param bool $uiAllowedTags
     * @return string
     */
    public function getStripped($string, $uiAllowedTags = false)
    {
        if ($uiAllowedTags) {
            return strip_tags($string, $this->htmlTagProvider->getAllowedTags());
        }
        return strip_tags($string);
    }

    /**
     * Get shorter text
     *
     * @param string $string
     * @param int $maxLength
     * @return string
     */
    public function getShort($string, $maxLength = self::MAX_STRING_LENGTH)
    {
        if (mb_strlen($string) > $maxLength) {
            $string = mb_substr($string, 0, $maxLength);
            $lastOccurrencePos = strrpos($string, ' ');
            if ($lastOccurrencePos !== false) {
                $string = mb_substr($string, 0, $lastOccurrencePos);
            }
        }

        return $string;
    }
}
