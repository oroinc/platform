<?php

namespace Oro\Bundle\UIBundle\Tools;

/**
 * Finds the differences between the subject and original strings and wraps them with the highlighting template.
 */
class TextHighlighter
{
    /**
     * @param string $subject The string you are analyzing or comparing to the original.
     * @param string $original The string to compare the $subject with.
     * @param string $highlightingTemplate The sprintf template to use for wrapping the differences. Example: <u>%s</u>.
     *
     * @return string The subject string with the differences wrapped with the highlighting template.
     */
    public function highlightDifferences(string $subject, string $original, string $highlightingTemplate): string
    {
        $original = trim($original);
        $subject = trim($subject);

        $originalParts = preg_split('/\s/u', $original, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);
        $subjectParts = preg_split('/\s/u', $subject, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);

        foreach (array_reverse($subjectParts, true) as $key => $subjectPart) {
            if (isset($originalParts[$key]) && $subjectPart[0] === $originalParts[$key][0]) {
                continue;
            }

            $subject = mb_substr($subject, 0, $subjectPart[1]) .
                sprintf($highlightingTemplate, mb_substr($subject, $subjectPart[1], mb_strlen($subjectPart[0]))) .
                mb_substr($subject, $subjectPart[1] + mb_strlen($subjectPart[0]));
        }

        return $subject;
    }
}
