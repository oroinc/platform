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
            $body = $tidy->repairString($bodyContent, $config, 'UTF8');
        } else {
            $body = $bodyContent;
            // get `body` content in case of html text
            if (preg_match('~<body[^>]*>(.*?)</body>~si', $bodyContent, $bodyText)) {
                $body = $bodyText[1];
            }
        }

        // clear `script` and `style` tags from content
        $body = preg_replace('/<(style|script).*?>.*?<\/\1>/s', '', html_entity_decode($body));

        // strip tags, clear extra spaces and non printed symbols and convert data to utf-8
        $clearBodyText = iconv(
            'UTF-8"',
            'UTF-8//IGNORE',
            preg_replace('/(\s\s+|\n+|[^[:print:]])/', ' ', trim(strip_tags($body)))
        );

        // trim the text content
        if (strlen($clearBodyText) > self::MAX_STRING_LENGTH) {
            $clearBodyText = substr($clearBodyText, 0, self::MAX_STRING_LENGTH);
            $lastOccurrencePos = strrpos($clearBodyText, ' ', null);
            if ($lastOccurrencePos !== false) {
                $clearBodyText = mb_substr($clearBodyText, 0, $lastOccurrencePos);
            }
        }

        return $clearBodyText;
    }
}
