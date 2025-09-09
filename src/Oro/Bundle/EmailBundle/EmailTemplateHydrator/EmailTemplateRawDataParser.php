<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\EmailTemplateHydrator;

/**
 * Parses an email template raw data.
 *
 * The raw data should be in the format:
 * @name = email_template_name
 * @subject = Subject of the email
 * @type = html
 *
 *  Email template content goes here.
 *
 * The rows starting with "@" are considered as metadata for the email template. Each metadata item is set to the
 * corresponding property of the email template - if it exists and is writable. Available metadata items are:
 *
 *  * @name - the machine name of the email template.
 *  * @subject - the subject of the email template.
 *  * @type - the type of the email template (e.g., html, txt).
 *  * @entityName - the entity class associated with the email template.
 *  * @isSystem - whether the email template is a system template.
 *  * @isEditable - whether the email template is editable.
 *  * @attachments - an array of attachments for the email template, specified as a string representation of an array.
 *      Can include either file placeholders (e.g., '{{ entity.file }}' or file paths '/path/to/file.txt').
 *
 * Wrap metadata items in `{# ... #}` to make it correctly handled if the raw data is a Twig file, e.g.:
 *  {# @subject = Subject of the email #}
 */
class EmailTemplateRawDataParser
{
    /**
     * @param string $rawData
     *
     * @return array<string,mixed>
     */
    public function parseRawData(string $rawData): array
    {
        $data = [];

        if (preg_match_all('#(?:\{\#\s*)?@(?P<name>\w+?)\s?=\s?(?P<value>.*?)(?:\s*\#\})?\n#i', $rawData, $matches)) {
            foreach ($matches[0] as $i => $match) {
                $name = trim($matches['name'][$i]);
                $value = trim($matches['value'][$i]);
                if (str_starts_with($name, 'is')) {
                    $value = (bool)$value;
                } elseif ((str_starts_with($value, '[') && str_ends_with($value, ']')) ||
                    (str_starts_with($value, '{') && str_ends_with($value, '}')) &&
                    !str_starts_with($value, '{{')) {
                    $value = $this->parseArrayString($value);
                }

                $data[$name] = $value;
                $rawData = trim(str_replace($match, '', $rawData));
            }
        }

        $data['content'] = $rawData;

        return $data;
    }

    /**
     * Parses a string representation of an array into a PHP array.
     * The input string should be in the format: ["value1", 'value2', ...]
     *
     * @param string $arrayString The input string, e.g. ["value1", 'value2', ...]
     *
     * @return array
     */
    private function parseArrayString(string $arrayString): array
    {
        // Replaces single quotes with double quotes for JSON compatibility.
        $jsonString = preg_replace_callback(
            '/\'(.*?)(?<!\\\\)\'/',
            static function ($matches) {
                // Only escape the outer quotes, leave content untouched
                return '"' . str_replace(['"', "\'"], ['\"', "'"], $matches[1]) . '"';
            },
            $arrayString
        );

        try {
            $array = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            // If JSON decoding fails, return the original value as a single-element array
            $array = [$arrayString];
        }

        return $array;
    }
}
