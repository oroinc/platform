Table of Contents
-----------------
 - [Localization](#localization)
 - [LocalizedFallbackValue](#localizedfallbackvalue)

Localization
============
Stores data for localization. Can be used for display interface in needed language and formatting.

Localization entity contains following fields:
* `name` (string) - unique name of the localization, using for system purposes and will not be available in 
user-interface
* `titles` (LocalizedFallbackValues[]) - set of translatable titles of Localization
* `languageCode` (string) - language code, for displaying full title of the language by code can be used formatter
`Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter`
* `formattingCode` (string) - formatting code, for displaying full title of the formatting by code can be used formatter
`Oro\Bundle\LocaleBundle\Formatter\FormattingCodeFormatter`
* `parentLocalization` (Localization) - parent Localization

LocalizedFallbackValue
======================

Stores translates of needed data for different localizations. Can be used for display interface in needed language and 
formatting.

LocalizedFallbackValue entity contains following fields:
* `fallback` (string) - fallback type
* `string` (string) - translation for short string
* `text` (string) - translation for long string (using if `string` is empty)
* `localization` (Localization) - localization

For retrieve translated value for the needed localization can be used
trait `Oro\Bundle\LocaleBundle\Entity\FallbackTrait` that provides method `getLocalizedFallbackValue`.
