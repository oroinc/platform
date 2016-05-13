Text Autocomplete Form Type
----------------------

#### Overview

You can add autocomplete functionality for text fields by using `oro_autocomplete` form type.

#### Form Type Configuration

Minimum required configuration with use of "autocomplete.alias":

```php
class ProductType extends AbstractType
{
/**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'tag',
            'oro_autocomplete',
            [
                'autocomplete' => [
                    'alias' => 'tags',
                ],
            ]
        );
    }

    // ...
}
```


Configuration without "autocomplete.alias":

```php
class ProductType extends AbstractType
{
/**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'tag',
            'oro_autocomplete',
            [
                'autocomplete' => [
                    'route_name' => 'oro_form_autocomplete_search',
                    'route_parameters' => ['name' => 'tags'],
                    'properties' => ['name'],
                ],
            ]
        );
    }

    // ...
}
```

**autocomplete.alias**

This option refers to a service configured with tag "oro_form.autocomplete.search_handler". Details of service configuration
described [here](#search-handler-configuration). If this option is set next options will be set if they are empty:
*autocomplete.route_name*, *autocomplete.route_parameters*, *autocomplete.properties*

**autocomplete.route_name**

Url of this route will be used to interact with search handler.
By default Oro\Bundle\FormBundle\Controller\AutocompleteController::searchAction is used
but you can implement your own action and use it by referencing it via *route_name*.

**autocomplete.componentModule**

You can implement your own autocomplete frontend component.
By default used `oro/autocomplete-component` with Bootstrap Typeahead plugin.

**autocomplete.properties**

List of properties that will be used in view to convert json object to string that will be displayed in select options
(optional if "autocomplete.alias" option is provided).

**autocomplete.selection_template_twig**

A name of Twig template that contain [underscore.js](http://underscorejs.org/) template.
This template will be used in dropdown list to render each result row.
Example of template:
```
<%= highlight(tag) %>
```
