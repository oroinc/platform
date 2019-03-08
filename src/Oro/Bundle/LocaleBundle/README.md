# OroLocaleBundle

OroLocaleBundle enables the locale management and a data localization support.

## Overview

This bundle provides the next localization tools:

- numbers and datetime formatting (intl is used)
- person names and postal addresses formatting
- dictionary of currencies, phone prefixes and default locales of countries

## Locale Settings

Locale Settings is a service of the Oro\Bundle\LocaleBundle\Model\LocaleSettings class with the "oro_locale.settings" ID.
This service can be used to get locale specific settings of the application, such as:
* locale based on localization
* language based on localization
* location
* calendar
* time zone
* list of person names formats
* list of addresses formats
* currency specific data
  * currency symbols based on currency codes
  * currency code, phone prefix, default locale based on country

Locale settings uses system configuration as a source.

See detailed [documentation](./Resources/doc/reference/locale-settings.md) for more details.

## Formatters

Please see [documentation](./Resources/doc/index.md) for more details.
