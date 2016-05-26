Forms Configuration
===================

Table of Contents
-----------------
 - [Overview](#overview)
 - [Validation](#validation)
 - [Forms](#forms)

Overview
--------

The Symfony [Validation Component](http://symfony.com/doc/current/book/validation.html) and [Forms Component](http://symfony.com/doc/current/book/forms.html) are used to validate and transform input data to an entity in [create](./actions.md#create-action) and [update](./actions.md#update-action) actions.

Validation
----------

The validation rules are loaded from _Resources/config/validation.yml_ and annotations as it is commonly done in Symfony applications. So, all validation rules are already defined for an entity are applicable in Data API as well.
Also, by default, Data API is used two validation groups: *Default* and *api*. If you need to add validation constrains that should be applicable in Data API only you can add them in *api* validation group.


Forms
-----

The forms are used in Data API are isolated from forms are used on UI. It is done to avoid collisions between them and to prevent unnecessary performance overhead in Data API.
As result of this isolation all form types, extensions and guessers are required in Data API should be registered separately. There are two ways how it can be done:

- using application configuration file
- tagging form types, extensions and guessers by appropriate tag

To register new form elements using application configuration file you can add _Resources/config/oro/app.yml_ in any bundle or use _app/config/config.yml_ of your application. The following example shows how it can be done:

```yaml
oro_api:
    form_types:
        - form.type.date # service id of "date" form type
    form_type_extensions:
        - form.type_extension.form.validator # service id of Symfony form validation extension
    form_type_guessers:
        - form.type_guesser.validator # service id of Symfony form type guesser based on validation constraints
    form_type_guesses:
        datetime: # data type
            form_type: datetime # the name of guessed form type
            options: # guessed form type options
                model_timezone: UTC
                view_timezone: UTC
                with_seconds: true
                widget: single_text
                format: "yyyy-MM-dd'T'HH:mm:ssZZZZZ" # HTML5
```

Already registered in Data API form elements you can find in [Resources/config/oro/app.yml](../config/oro/app.yml).

Also new form elements can be added using appropriate dependency injection tags. The following table shows all available tags.

| Tag | Description |
| --- | --- |
| oro.api.form.type | Create a new form type |
| oro.api.form.type_extension | Create a new form extension |
| oro.api.form.type_guesser | Add your own logic for "form type guessing" |

An example:

```yaml
    acme.form.type.datetime:
        class: Acme\Bundle\AcmeBundle\Form\Type\DateTimeType
        tags:
            - { name: form.type, alias: acme_datetime } # allow to use the form type on UI 
            - { name: oro.api.form.type, alias: acme_datetime } # allow to use the form type in Data API

    acme.form.extension.datetime:
        class: Acme\Bundle\AcmeBundle\Form\Extension\DateTimeExtension
        tags:
            - { name: form.type_extension, alias: acme_datetime } # add the form extension to UI forms
            - { name: oro.api.form.type_extension, alias: acme_datetime } # add the form extension to Data API forms

    acme.form.guesser.test:
        class: Acme\Bundle\AcmeBundle\Form\Guesser\TestGuesser
        tags:
            - { name: form.type_guesser } # add the form type guesser to UI forms
            - { name: oro.api.form.type_guesser } # add the form type guesser to Data API forms
```

To switch between general and Data API forms [Processor\Shared\InitializeApiFormExtension](../../Processor/Shared/InitializeApiFormExtension.php) and [Processor\Shared\RestoreDefaultFormExtension](../../Processor/Shared/RestoreDefaultFormExtension.php) processors can be used.

A form for a particular entity is built on the fly based on [Data API configuration](./configuration.md) and an entity metadata. It is performed by [Processor\Shared\BuildFormBuilder](../../Processor/Shared/BuildFormBuilder.php) processor
