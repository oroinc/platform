<?php

namespace Oro\Bundle\ApiBundle\Util;

/**
 * Provides a set of static methods to simplify working with "{@inheritdoc}"
 * and "{@inheritdoc:description}" placeholders in descriptions of API resources.
 */
class InheritDocUtil
{
    /** The inheritdoc placeholder */
    private const PLACEHOLDER = '{@inheritdoc}';

    /** The placeholder for the entity or field description */
    private const PLACEHOLDER_DESCRIPTION = '{@inheritdoc:description}';

    /**
     * Checks whether the given string contains the inheritdoc placeholder.
     */
    public static function hasInheritDoc(?string $text): bool
    {
        return $text && str_contains($text, self::PLACEHOLDER);
    }

    /**
     * Replaces the inheritdoc placeholder in $text with $inheritText.
     */
    public static function replaceInheritDoc(string $text, ?string $inheritText): string
    {
        return self::doReplaceInheritDoc(self::PLACEHOLDER, $text, $inheritText);
    }

    /**
     * Checks whether the given string contains the placeholder for the entity or field description.
     */
    public static function hasDescriptionInheritDoc(?string $text): bool
    {
        return $text && str_contains($text, self::PLACEHOLDER_DESCRIPTION);
    }

    /**
     * Replaces the placeholder for the entity or field description inheritdoc in $text with $inheritText.
     */
    public static function replaceDescriptionInheritDoc(string $text, ?string $inheritText): string
    {
        return self::doReplaceInheritDoc(self::PLACEHOLDER_DESCRIPTION, $text, $inheritText);
    }

    /**
     * Replaces the given inheritdoc placeholder in $text with $inheritText.
     */
    private static function doReplaceInheritDoc(string $placeholder, string $text, ?string $inheritText): string
    {
        $inheritText = (string)$inheritText;
        // try avoid paragraph tag inside another paragraph tag, e.g.:
        // - <p><p>inherited text</p></p>
        // - <p><p>inherited</p><p>text</p></p>
        // - <p>some <p>inherited</p> text</p>
        // try to avoid paragraph tag inside inlining inheritdoc,
        // e.g.if text is "text {@inheritdoc}" and injected text is "<p>injected</p>",
        // the result should be "text injected", not "text <p>injected</p>"
        $placeholderWithParagraph = '<p>' . $placeholder . '</p>';
        if (str_contains($text, $placeholderWithParagraph)) {
            if (self::hasParagraphTag($inheritText)) {
                return str_replace($placeholderWithParagraph, $inheritText, $text);
            }
        } elseif (self::isEnclosedByParagraphTag($inheritText)) {
            return str_replace($placeholder, self::removeEnclosedParagraphTag($inheritText), $text);
        }

        return str_replace($placeholder, $inheritText, $text);
    }

    private static function hasParagraphTag(string $text): bool
    {
        return str_contains($text, '<p>');
    }

    private static function isEnclosedByParagraphTag(string $text): bool
    {
        if (\strlen($text) < 7 || false === strpos($text, '</p>', -4) || 0 !== strncmp($text, '<p>', 3)) {
            return false;
        }

        return false === strpos($text, '<p>', 3);
    }

    private static function removeEnclosedParagraphTag(string $text): string
    {
        return substr($text, 3, -4);
    }
}
