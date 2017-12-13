# Forms and Validators Configuration

 - [Overview](#overview)
 - [Validation](#validation)
 - [Forms](#forms)

## Overview

The Symfony [Validation Component](http://symfony.com/doc/current/book/validation.html) and [Forms Component](http://symfony.com/doc/current/book/forms.html) are used to validate and transform the input data in the [create](./actions.md#create-action), [update](./actions.md#update-action), [update_relationship](./actions.md#update_relationship-action), [add_relationship](./actions.md#add_relationship-action), and [delete_relationship](./actions.md#delete_relationship-action) actions.

## Validation

The validation rules are loaded from `Resources/config/validation.yml` and annotations as it is commonly done in Symfony applications. So, all validation rules defined for an entity apply to the data API as well.
By default, the data API uses two validation groups: **Default** and **api**. If you need to add validation constraints that should apply to the data API only, add them to the **api** validation group.

If the input data violates validation constraints, they will be automatically converted to [validation errors](./processors.md#error-handling) that help build the correct response of the data API. The conversion is performed by the [CollectFormErrors](../../Processor/Shared/CollectFormErrors.php) processor. By default, the HTTP status code for validation errors is `400 Bad Request`. If you need to change it, you can do it in the following ways:

- Implement [ConstraintWithStatusCodeInterface](../../Validator/Constraints/ConstraintWithStatusCodeInterface.php) in your constraint class.
- Implement a custom constraint text extractor. The API bundle has the [default implementation of constraint text extractor](../../Request/ConstraintTextExtractor.php). To add a new extractor, create a class that implements [ConstraintTextExtractorInterface](../../Request/ConstraintTextExtractorInterface.php) and tag it with the `oro.api.constraint_text_extractor` in the dependency injection container. This service can be also used to change an error code and type for a validation constraint.

The following example shows how to add validation constraints to API resources using the `Resources/config/oro/api.yml` configuration file:

```yaml
api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            fields:
                primaryEmail:
                    form_options:
                        constraints:
                            # add Symfony\Component\Validator\Constraints\Email validation constraint
                            - Email: ~
                userName:
                    form_options:
                        constraints:
                            # add Symfony\Component\Validator\Constraints\Length validation constraint
                            - Length:
                                max: 50
                            # add Acme\Bundle\AcmeBundle\Validator\Constraints\Alphanumeric validation constraint
                            - Acme\Bundle\AcmeBundle\Validator\Constraints\Alphanumeric: ~

```


## Forms

The data API forms are isolated from the UI forms. This helps avoid collisions and prevent unnecessary performance overhead in the data API.
Consequently, all the data API form types, extensions, and guessers should be registered separately. There are two ways of how to complete this:

- Use the application configuration file.
- Tag the form elements by appropriate tags in the dependency injection container.

To register a new form elements using the application configuration file, add `Resources/config/oro/app.yml` in any bundle or use `app/config/config.yml` of your application:

```yaml
api:
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

You can find the already registered data API form elements in [Resources/config/oro/app.yml](../config/oro/app.yml).

If you need to add new form elements can by tagging them in the dependency injection container, use the tags from the following table:

| Tag | Description |
| --- | --- |
| oro.api.form.type | Create a new form type. |
| oro.api.form.type_extension | Create a new form extension. |
| oro.api.form.type_guesser | Add a custom logic for the "form type guessing". |

**Example:**

```yaml
    acme.form.type.datetime:
        class: Acme\Bundle\AcmeBundle\Form\Type\DateTimeType
        tags:
            - { name: form.type, alias: acme_datetime } # Enable usage of the form type on the UI.
            - { name: oro.api.form.type, alias: acme_datetime } # Enable usage of the form type in the data API.

    acme.form.extension.datetime:
        class: Acme\Bundle\AcmeBundle\Form\Extension\DateTimeExtension
        tags:
            - { name: form.type_extension, alias: acme_datetime } # Add the form extension to the UI forms.
            - { name: oro.api.form.type_extension, alias: acme_datetime } # Add the form extension to the data API forms.

    acme.form.guesser.test:
        class: Acme\Bundle\AcmeBundle\Form\Guesser\TestGuesser
        tags:
            - { name: form.type_guesser } # Add the form type guesser to the UI forms.
            - { name: oro.api.form.type_guesser } # Add the form type guesser to the data API forms.
```

To switch between the general and data API forms, use the [Processor\Shared\InitializeApiFormExtension](../../Processor/Shared/InitializeApiFormExtension.php) and [Processor\Shared\RestoreDefaultFormExtension](../../Processor/Shared/RestoreDefaultFormExtension.php) processors.

The [Processor\Shared\BuildFormBuilder](../../Processor/Shared/BuildFormBuilder.php) processor builds the form for a particular entity on the fly based on the [data API configuration](./configuration.md) and the entity metadata.
