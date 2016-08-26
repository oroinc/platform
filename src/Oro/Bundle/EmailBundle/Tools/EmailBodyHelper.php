<?php

namespace Oro\Bundle\EmailBundle\Tools;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

class EmailBodyHelper
{
    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /**
     * EmailBodyHelper constructor.
     *
     * @param HtmlTagHelper $htmlTagHelper
     */
    public function __construct(HtmlTagHelper $htmlTagHelper)
    {
        $this->htmlTagHelper = $htmlTagHelper;
    }

    /**
     * Returns the plain text representation of email body
     *
     * @param string $bodyContent
     *
     * @return string
     */
    public function getClearBody($bodyContent)
    {
        if (extension_loaded('tidy')) {
            $config = [
                'show-body-only' => true,
                'clean'          => true,
                'hide-comments'  => true
            ];
            $tidy = new \tidy();
            $out = $tidy->repairString($bodyContent, $config, 'UTF8');
            $body = preg_replace('/<script\b[^>]*>(.*?)<\/script>/si', '', $out);
        } else {
            $body = $this->htmlTagHelper->purify($bodyContent);
        }

        return preg_replace('/\s\s+/', ' ', $this->htmlTagHelper->stripTags($body));
    }
}
