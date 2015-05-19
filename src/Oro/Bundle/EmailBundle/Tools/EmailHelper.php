<?php

namespace Oro\Bundle\EmailBundle\Tools;

use Oro\Bundle\FormBundle\Form\DataTransformer\SanitizeHTMLTransformer;

class EmailHelper
{
    const MAX_DESCRIPTION_LENGTH = 500;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @param string|null $cacheDir
     */
    public function __construct($cacheDir = null)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * @param string $content
     * @return string
     */
    public function getStrippedBody($content)
    {
        $transformer = new SanitizeHTMLTransformer(null, $this->cacheDir);
        $content = $transformer->transform($content);
        return strip_tags($content);
    }

    /**
     * Get shorter email body
     *
     * @param string $content
     * @param int $maxLength
     * @return string
     */
    public function getShortBody($content, $maxLength = self::MAX_DESCRIPTION_LENGTH)
    {
        if (mb_strlen($content) > $maxLength) {
            $content = mb_substr($content, 0, $maxLength);
            $lastOccurrencePos = strrpos($content, ' ');
            if ($lastOccurrencePos !== false) {
                $content = mb_substr($content, 0, $lastOccurrencePos);
            }
        }

        return $content;
    }
}
