What is layout?
===============

A **layout** defines the visual structure of the user interface element such as a page or a widget. To put it simpler, **layout** is a
recursive system that knows how an element should be positioned and drawn. The [Layout](../../../../Component/Layout/README.md) component provides an easy way to manipulate
this structure.

Let's take a look at any page in an application, what do we see? Usually, the content could be separated in
a set of blocks grouped by content or structure. 

So, imagine that we have the following page structure:

![Layout example](./images/layout.png "Layout example")

We split the page into the following blocks hierarchy:

* root
   * header
   * body
     * sidebar
     * main content
   * footer

Each of these blocks will have children in the final structure, so they represent a *container*.

- **Container** - is a block type that is responsible for holding and rendering its children.
- **Final block** - block type that renders content based on data, but it can not have children.

Each block has a **type class** that is responsible for passing options and data into view, and building the
inner structure of the block in *containers*.

The **layout** should be built by providing a set of actions named a **[layout update](./layout_update.md)**.
Layout updates can be defined for a specific route and a specific [theme](./theme_definition.md).

See the [Layout](../../../../Component/Layout/README.md) component documentation for further detailed explanation.

Block types
-----------

The **OroLayoutBundle** introduces a set of block types that allow to easily build HTML layout structure.

| Type name | Type class | Default HTML output |
|-----------|------------|-------------|
| `root` | | `<html>` |
| `head` | | `<head>` |
| `title` | [TitleType.php](../../Layout/Block/Type/TitleType.php) | `<title>` |
| `meta` | [MetaType.php](../../Layout/Block/Type/MetaType.php) | `<meta>` |
| `style` | [StyleType.php](../../Layout/Block/Type/StyleType.php) | `<style>` with content or `<link>` with external resource |
| `script` | [ScriptType.php](../../Layout/Block/Type/ScriptType.php) | `<script>` |
| `external_resource` | [ExternalResourceType.php](../../Layout/Block/Type/ExternalResourceType.php) | `<link>` |
| `body` | | `<body>` |
| `form_start` | [FormStartType.php](../../Layout/Block/Type/FormStartType.php) | `<form>` |
| `form_end` | [FormEndType.php](../../Layout/Block/Type/FormEndType.php) | `</form>` |
| `form` | [FormType.php](../../Layout/Block/Type/FormType.php) | Adds form fields based on the Symfony form |
| `form_field` | [FormFieldType.php](../../Layout/Block/Type/FormFieldType.php) |  Block will be rendered differently depending on the field type of the Symfony form |
| `fieldset` | | `<fieldset>` |
| `link` | [LinkType.php](../../Layout/Block/Type/LinkType.php) | `<a>` |
| `list` | | `<ul>` |
| `ordered_list` | [OrderedListType.php](../../Layout/Block/Type/OrderedListType.php) | `<ol>` |
| `list_item` | | `<li>`, this block type can be used if you want to control rendering of `li` tag and its attributes |
| `text` | | Text node |
| `input` | [InputType.php](../../Layout/Block/Type/InputType.php) | Input node |
| `button` | | `<button>` or `<input type="submit/reset/button">` |
| `button_group` | | Nothing, this is just a logical grouping of buttons. You can define how to render the button group in your application |

You can create custom block type by providing DI configuration for it. Configuration provides possibility to set name and name of a parent, and setup options of the block. See below examples.

Simple block type:
```yaml
services:
    acme_demo.block_type.datetime:
        parent: oro_layout.block_type.abstract_configurable
        calls:
            - [setOptions, [{datetime: {required: true}, format: {default: 'd-m-Y'}, timezone: ~}]]
            - [setName, ['datetime']]
        tags:
             - { name: layout.block_type, alias: datetime }
```

`setOptions` is associative array where key is the name of option, and value is a array with 'default' and 'require' possible keys. Also you can provide '~' as a value what mean define option.

Container block type:
```yaml
services:
    acme_demo.block_type.sidebar:
        parent: oro_layout.block_type.abstract_configurable_container
        calls:
            - [setName, ['sidebar']]
        tags:
             - { name: layout.block_type, alias: sidebar }
```

Block type inherited from "text" type:
```yaml
services:
    acme_demo.block_type.title:
        parent: oro_layout.block_type.abstract_configurable
        calls:
            - [setOptions, [{level: {default: 1}}]]
            - [setName, ['title']]
            - [setParent, ['text']]
        tags:
             - { name: layout.block_type, alias: title }
```

If you want to create block type with custom properties mapping extend your block type class from `Oro\Component\Layout\Block\Type\AbstractType` or implement `Oro\Component\Layout\BlockTypeInterface`.

It's recomended to place custom block types configuration to `Resource\config\block_type.yml` file in your bundle.
