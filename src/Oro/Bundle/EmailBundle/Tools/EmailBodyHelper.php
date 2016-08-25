<?php

namespace Oro\Bundle\EmailBundle\Tools;

class EmailBodyHelper
{
    /**
     * Returns clear email body.
     * In case if email body is html, returns the <body> tag content with cleaned `style` and 'script' tags
     *
     * @param string $bodyContent
     *
     * @return string
     */
    public static function getClearBody($bodyContent)
    {
        // get `body` content in case of html text
        if (preg_match('~<body[^>]*>(.*?)</body>~si', $bodyContent, $body)) {
            $bodyContent = $body[1];
        }
        // clear `style` tags with content
        $bodyContent = preg_replace('/<style\b[^>]*>(.*?)<\/style>/si', '', $bodyContent);
        // clear `script` tags with content
        $bodyContent = preg_replace('/<script\b[^>]*>(.*?)<\/script>/si', '', $bodyContent);

        return $bodyContent;
    }
}
