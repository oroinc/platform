# Forms and Validators Configuration

 - [Overview](#overview)
 - [Validation](#validation)
 - [Forms](#forms)

## Overview

The Symfony [Validation Component](http://symfony.com/doc/current/book/validation.html) and [Forms Component](http://symfony.com/doc/current/book/forms.html) are used to validate and transform the input data in the [create](./actions.md#create-action), [update](./actions.md#update-action), [update_relationship](./actions.md#update_relationship-action), [add_relationship](./actions.md#add_relationship-action), and [delete_relationship](./actions.md#delete_relationship-action) actions.

## Validation

The validation rules are loaded from `Resources/config/validation.yml` and annotations as it is commonly done in Symfony applications. So, all validation rules defined for an entity are applied to the data API as well.
By default, the data API uses two validation groups: **Default** and **api**. If you need to add validation constraints that should apply to the data API only, add them to the **api** validation group.

In case a validation rule cannot be implemented as a regular validation constraint due to its complexity
you can implement it as a processor for `post_validate` event of
[customize_form_data](./actions.md#customize_form_data-action) action.
Pay your attention on [FormUtil](../../Form/FormUtil.php) class, it contains methods that may be useful in such processor.

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

To register a new form elements using the application configuration file, add `Resources/config/oro/app.yml` in any bundle or use `config/config.yml` of your application:

```yaml
api:
    form_types:
        - Symfony\Component\Form\Extension\Core\Type\DateType # the class name of a form type
        - form.type.date # the service id of a form type
    form_type_extensions:
        - form.type_extension.form.http_foundation # service id of a form type extension
    form_type_guessers:
        - acme.form.type_guesser # service id of a form type guesser
    form_type_guesses:
        datetime: # data type
            form_type: Symfony\Component\Form\Extension\Core\Type\DateTimeType # the guessed form type
            options: # guessed form type options
                model_timezone: UTC
                view_timezone: UTC
                with_seconds: true
                widget: single_text
                format: "yyyy-MM-dd'T'HH:mm:ssZZZZZ" # HTML5
```

**Please note** that the `form_types` section can contain either the class name or the service id of a form type.
Usually the service id is used if a form type depends on other services in the dependency injection container.

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
            # Enable usage of the form type on the UI.
            - { name: form.type, alias: acme_datetime }
            # Enable usage of the form type in the data API.
            - { name: oro.api.form.type, alias: acme_datetime }

    acme.form.extension.datetime:
        class: Acme\Bundle\AcmeBundle\Form\Extension\DateTimeExtension
        tags:
            # Add the form extension to the UI forms.
            - { name: form.type_extension, extended_type: Acme\Bundle\AcmeBundle\Form\Type\DateTimeType }
            # Add the form extension to the data API forms.
            - { name: oro.api.form.type_extension, extended_type: Acme\Bundle\AcmeBundle\Form\Type\DateTimeType }

    acme.form.guesser.test:
        class: Acme\Bundle\AcmeBundle\Form\Guesser\TestGuesser
        tags:
            # Add the form type guesser to the UI forms.
            - { name: form.type_guesser }
            # Add the form type guesser to the data API forms.
            - { name: oro.api.form.type_guesser }
```

To switch between the general and data API forms, use the [Processor\Shared\InitializeApiFormExtension](../../Processor/Shared/InitializeApiFormExtension.php) and [Processor\Shared\RestoreDefaultFormExtension](../../Processor/Shared/RestoreDefaultFormExtension.php) processors.

The [Processor\Shared\BuildFormBuilder](../../Processor/Shared/BuildFormBuilder.php) processor builds the form for a particular entity on the fly based on the [data API configuration](./configuration.md) and the entity metadata.
