OroEmbeddedFormBundle
=====================

Bundle provides mechanism to create forms, embed them into third party sites and store and view data submitted via them.
Basically `EmbeddedForm` is `FormType`. Also it contains custom css and success message.

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

Other possible option is `type`. If it is defined then it will be used as form type (instead of actual service defined by `class` parameter).
This kind of form types appear in drop down list on the create and update embedded form pages.

## Default FormType CSS and Success Message
By default CSS and Success Message fields on the create new embedded form page are empty.
To add default styles or default success message FormType must implement `Oro\Bundle\EmbeddedFormBundle\Form\Type\EmbeddedFormInterface`.

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

## Custom form layout
For backward compatibility, the legacy mechanism of customizing embedded forms layout is supported:
for this a FormType should implement `Oro\Bundle\EmbeddedFormBundle\Form\Type\CustomLayoutFormInterface`.

However, it is advisable to use Layouts engine from the LayoutBundle to customize the form layout.
Embedded forms use the `embedded_form` layout theme and layout update files should be placed in `Acme\Bundle\DemoBundle\Resources\layouts\embedded_form` directory.

Layout update files can be placed in subdirectory corresponding to a route name (e.g. `oro_embedded_form_submit`, `oro_embedded_form_success`) if it needs to be applied a specific action only.
Please, refer to the [LayoutBundle](../LayoutBundle/Resources/doc/index.md) documentation for more information.

Let's consider an example when we need to move the email field before the first name field on the embedded form:

**Example**
```yml
oro_layout:
    actions:
        - @move:
            id:        embedded_form_email         # embedded_form_ is field prefix
            parentId:  embedded_form               # target parent block
            siblingId: embedded_form_firstName
            prepend:   true                        # put moved block before sibling

    conditions:
        @eq:
            - $context.embedded_form_type
            - 'acme_demo.form.embedded_form' # form type name in container
```

We need to specify layout update conditions since all embedded forms are using the same route.
The condition should check that your custom form type is equal to the form type stored in the layout context.
This will make sure that your layout updates are loaded only for the your embedded form type.



