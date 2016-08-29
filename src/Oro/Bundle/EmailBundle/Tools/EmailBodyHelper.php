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
            $body = $tidy->repairString($bodyContent, $config, 'UTF8');
        } else {
            // get `body` content in case of html text
            if (preg_match('~<body[^>]*>(.*?)</body>~si', $bodyContent, $body)) {
                $body = $body[1];
            }
            // clear `style` tags with content
            $body = preg_replace('/<style\b[^>]*>(.*?)<\/style>/si', '', $body);
        }

        $body = preg_replace('/<script\b[^>]*>(.*?)<\/script>/si', '', $body);

        return preg_replace('/\s\s+/', ' ', $this->htmlTagHelper->stripTags($body));
    }
}
