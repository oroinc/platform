# Oro\Bundle\LocaleBundle\Entity\Localization

## ACTIONS

### get

Retrieve a specific localization record.

{@inheritdoc}

### get_list

Retrieve a collection of localization records.

{@inheritdoc}

## FIELDS

### languageCode

The language code.

## SUBRESOURCES

### childLocalizations

#### get_subresource

Retrieve a record of child localization assigned to a specific localization record.

#### get_relationship

Retrieve the ID of child localization record assigned to a specific localization record.

### parentLocalization

#### get_subresource

Retrieve a record of parent localization assigned to a specific localization record.

#### get_relationship

Retrieve the ID of parent localization record assigned to a specific localization record.

### titles

#### get_subresource

Retrieve the service records that store the localization titles localized data.

#### get_relationship

Retrieve the IDs of service records that store the localization titles localized data.
