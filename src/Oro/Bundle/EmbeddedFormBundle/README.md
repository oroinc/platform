Oro Embedded Form Bundle
=======================

Bundle provides mechanism to create forms, embed them into third party sites and store and view data submitted via them.
Basically `EmbeddedForm` is `Channel` plus `FormType`. Also it contains custom css and success message.

## UI
Menu item that leads to list of embedded forms is under `System` menu.
For list, view, update pages standard UI components are used - grids, filters, sorters, forms.
View page combined with "Form Preview" section and "Get code" section.

## Adding new FormType
FormTypes that can be used in embedded forms are services that are tagged with `oro_embedded_form` tag.

**Example:**
```yml
parameters:
    acme_demo.form.embedded_form.class: Acme\Bundle\DemoBundle\Form\Type\SomeFormType

services:
    acme_demo.form.embedded_form:
        class: %acme_demo.form.embedded_form.class%
        tags:
            - { name: oro_embedded_form, label: 'Embedded Form Type Label Here' }
            - { name: form.type, alias: acme_demo_some_form_type }
```

`label` option is translatable text used in select box on create/update embedded form. If omitted - service id will be used instead.

Other possible option is `type`. If it is defined then it will be used as form type (instead of actual service defined by `class` parameter). This kind of form types appear in drop down list on the create and update embedded form pages.

FormType can be based on `channel_aware_form` which already contains `channel` field:
```php
    ....
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_channel_aware_form';
    }
    ....
```


## Default FormType CSS and Success Message
By default CSS and Success Message fields on the create new embedded form page are empty.
To add default styles or default success message FormType must implement `Oro\Bundle\EmbeddedFormBundle\Form\Type\EmbeddedFormInterface`.

## Custom form layout
By default all forms have common layout `OroEmbeddedFormBundle:EmbedForm:formLayout.html.twig`
If FormType has to have different layout then FormType must implement `Oro\Bundle\EmbeddedFormBundle\Form\Type\CustomLayoutFormTypeInterface`.

## Changing FormType
It is possible to change embedded form FormType on/after creation.
Related default styles and default success message will be pulled. If current css and success message are changed - confirmation dialog will appear.

## Success Message
This message will appear after success form submitting. And form will disappear.
To add back link to success message use following syntax `{back_link|Back link text}`.

## Get a Code
"Get code" section is located on the view embedded form page. Example:

```html
<div id="embedded-form-container-2b975a6c-844f-11e3-a31b-001fe24ecc11"></div>
<script type="text/javascript" src="http://example.com/bundles/oroembeddedform/js/embed.form.js"></script>
<script type="text/javascript">
    new EmbedForm({
        container: 'embedded-form-container-2b975a6c-844f-11e3-a31b-001fe24ecc11',
        iframe: {
            src: "http://example.com/embedded-form/submit/2b975a6c-844f-11e3-a31b-001fe24ecc11",
            width: 640,
            height: 800,
            frameBorder: 0
        }
    });
</script>
```
`iframe` object properties will be directly mapped onto create iframe element properties. So, it possible to change iframe sizes or add/remove frame border.
