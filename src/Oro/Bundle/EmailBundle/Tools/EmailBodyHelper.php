<?php

namespace Oro\Bundle\EmailBundle\Tools;

class EmailBodyHelper
{
    const MAX_STRING_LENGTH = 500;

    /**
     * Returns the plain text representation of email body
     *
     * @param string $bodyContent
     *
     * @return string
     */
    public function getTrimmedClearText($bodyContent)
    {
        if (extension_loaded('tidy')) {
            $config = [
                'show-body-only' => true,
                'clean'          => true,
                'hide-comments'  => true
            ];
            $tidy = new \tidy();
            $body = $tidy->repairString($bodyContent, $config, 'utf8');
        } else {
            $body = $bodyContent;
            // get `body` content in case of html text
            if (preg_match('~<body[^>]*>(.*?)</body>~si', $bodyContent, $bodyText)) {
                $body = $bodyText[1];
            }
        }
        // Clear style and script tags
        $body = strip_tags(html_entity_decode(preg_replace('/<(style|script).*?>.*?<\/\1>/su', '', $body)));
        // Clear non printed symbols
        $body = preg_replace('/(?>[\x00-\x1F]|\xC2[\x80-\x9F]|\xE2[\x80-\x8F]{2}|'
            . '\xE2\x80[\xA4-\xA8]|\xE2\x81[\x9F-\xAF])/u', ' ', $body);

        $body = trim(preg_replace('/(\s\s+|\n+)/u', ' ', $body));
        // trim the text content
        if (strlen($body) > self::MAX_STRING_LENGTH) {
            $body = substr($body, 0, self::MAX_STRING_LENGTH);
            $lastOccurrencePos = strrpos($body, ' ', null);
            if ($lastOccurrencePos !== false) {
                $body = substr($body, 0, $lastOccurrencePos);
            }
        }

        return $body;
    }
}
