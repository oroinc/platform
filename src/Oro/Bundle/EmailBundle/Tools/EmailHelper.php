<?php

namespace Oro\Bundle\EmailBundle\Tools;

use Oro\Bundle\FormBundle\Form\DataTransformer\SanitizeHTMLTransformer;

use Oro\Bundle\EmailBundle\Entity\EmailBody;

class EmailHelper
{
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
     * If body is html, get content before first quote div, otherwise return body as it is
     *
     * @param EmailBody $body
     * @return string
     */
    public function getOnlyLastAnswer(EmailBody $body)
    {
        if (!$body->getBodyIsText()) {
            preg_match('/(.+)(<div class="quote">)/siU', $body->getBodyContent(), $match);

            if (isset($match[1])) {
                return $match[1];
            }
        }

        return $body->getBodyContent();
    }

    /**
     * Get shorter email body
     *
     * @param string $content
     * @param int $maxLength
     * @return string
     */
    public function getShortBody($content, $maxLength = 60)
    {
        if (mb_strlen($content) > $maxLength) {
            $content = mb_substr($content, 0, $maxLength);
            $lastOccurrencePos = strrpos($content, ' ');
            if ($lastOccurrencePos !== false) {
                $content = mb_substr($content, 0, $lastOccurrencePos);
            }
            $content .= '...';
        }

        return $content;
    }
}
