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
        /**
         * @todo: Should be refactored or deleted in scope of BAP-11622
         */
        if (extension_loaded('tidy')) {
            $config = [
                'show-body-only' => true,
                'clean'          => true,
                'hide-comments'  => true
            ];
            $tidy = new \tidy();
            $body = $tidy->repairString($bodyContent, $config, 'UTF8');
        } else {
            $body = $bodyContent;
            // get `body` content in case of html text
            if (preg_match('~<body[^>]*>(.*?)</body>~si', $bodyContent, $bodyText)) {
                $body = $bodyText[1];
            }
        }

        // clear `script` and `style` tags from content
        $body = preg_replace('/<(style|script).*?>.*?<\/\1>/s', '', $body);

        return preg_replace('/(\s\s+|\n+|[^[:print:]])/', ' ', $this->htmlTagHelper->stripTags($body));
    }
}
