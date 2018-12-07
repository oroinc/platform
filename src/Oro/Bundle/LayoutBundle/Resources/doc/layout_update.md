# Layout Update

## Overview

A **layout update** is a set of actions that should be performed with the **[layout](what_is_layout.md)** in order to
customize the page look depending on our needs. The **layout update** may be performed manually
(via the [LayoutBuilder](../../../../Component/Layout/LayoutBuilder.php)), or collected by the *OroPlatform* loaders automatically.

## Loaders

A loader is responsible for reading, preparing and caching the content of the update file. Out of the box, *OroPlatform* supports *PHP* and *YAML* loaders that load layout updates matched by the file extension. Each of these loaders generates *PHP* classes in the cache directory (`var/cache/{env}/layouts`). For the production mode, validation and generation of cache files is performed by the *optional cache warmer* and could be enforced by running the `bin/console cache:warmup --env=prod` shell command.

## Yaml Syntax

This section covers the basic layout update file structure and syntax that can be extended for advanced use.
The **layout update** file should have `layout` as a root node, and may consist of the `actions` and `conditions` nodes.

### Actions

**Actions** is an array node with a set of actions to execute. Each action is usually compiled as a separate call to the corresponding method of the [LayoutManipulatorInterface](../../../../Component/Layout/LayoutManipulatorInterface.php).

Here is the list of the available actions:

| Action name | Description |
|------- |-------------|
| `add` | Add a new item to the layout |
| `addTree` | Add multiple items defined in the tree hierarchy. [See expanded doc](#addtree-action). |
| `remove` | Remove the item from the layout. |
| `move` | Change the layout item position. Could be used to change the parent item or ordering position. |
| `addAlias` | Add an alias for the layout item. Could be used for backward compatibility. |
| `removeAlias` | Remove the alias previously defined for the layout item. |
| `setOption` | Set the option value for the layout item. |
| `appendOption` | Add a new value in addition to existing values of an option of the layout item. |
| `subtractOption` | Remove the existing value from an option of the layout item. |
| `replaceOption` | Replace one value with another value of an option of the layout item. |
| `removeOption` | Unset the option from the layout item. |
| `changeBlockType` | Change the block type of the layout item. |
| `setBlockTheme` | Define the theme file where the renderer should look for block templates. |
| `clear` | Clear the state of the manipulator. This can prevent execution of all the previously collected actions. |

Actions definition is processed as a multidimensional array where the key is the **action name** prefixed by the `@` sign, and the value is a list of arguments that is passed directly to the proxied method call. Arguments can be passed as a sequential list, or an associative array.

**Example**

```yaml
layout:
    actions:
        - '@add': # Sequential list
            - block_id
            - parent_block_id
            - block_type
        - '@remove': # Named arguments
            id: content
```

The previous example is compiled in the following PHP code:

```php
$layoutManipulator->add( 'block_id', 'parent_block_id', 'block_type' );
$layoutManipulator->remove( 'content' );
```

Optional parameters can be skipped when named arguments are used. In the following example, we skip the optional argument `parentId` that will be set to the default value automatically.

**Example**

```yaml
layout:
    actions:
        - '@move':
            id:        block_id
            siblingId: sibling_block_id
```

#### AddTree Action

You can add a set of blocks with the `addTree` action. It requires two nodes to be defined - `items` and `tree`.

In the **Items** node, specify the list of block definitions. Use the **block id**  as the item key. This will result in the `@add` action for every specified block.

In the **Tree** node, arrange the items into the desired hierarchy. Use the existing parent **block id** as the first node of the tree. The items will be added as its children.
 

**Example**

```yaml
layout:
    actions:
        - '@addTree':
            items:
                head:
                    blockType:   head
                meta_charset:
                    blockType:   meta
                    options:
                        charset: 'utf-8'
                content:
                    blockType: body
            tree:
                root:
                    head:
                        meta_charset: ~
                    content: ~
```

**Note:** The tree definition should refer only to the *items* that are declared in the same `@addTree` action, otherwise a syntax error will occur.

Leaves of the tree can be defined as sequentially ordered array items. However, you should take into account the fact that *YAML* syntax does not allow mixing both approaches in the same array node. We therefore recommend to use the associative array syntax.

### Conditions

**Conditions** is an array node which contains conditions that must be satisfied for **layout update** to be executed.

As an example, assume that a set of actions should be executed only for a page that is currently served to a mobile device.

The syntax of conditions declaration is very similar to *actions*, except that it should contain a single condition.

Special grouping conditions (such as `or`, `and`) can be utilized to combine multiple conditions.

**Example**

```yaml
layout:
    actions:
        ....
    conditions: 'context["is_mobile"] == true or context["navbar_position"] == "top"'
```

**[Layout context](./layout_context.md)** could be accessed through the condition expressions by referencing to `$context` variable.

Please, refer to the [Symfony expression syntax](http://symfony.com/doc/current/components/expression_language/syntax.html) documentation for a more detailed explanation.

## PHP Syntax

*PHP* layout update allows developers to write the update instructions directly. It is highly recommended to use it only when other *config based* loaders do not satisfy your specific requirements. Opening and closing *PHP* tags can be omitted. It is also recommended to use an opening tag and a *PHPDoc* variable type hinting to get your IDE autocomplete working.

**Example**

```php
/** @var Oro\Component\Layout\LayoutManipulatorInterface $layoutManipulator */
/** @var Oro\Component\Layout\LayoutItemInterface $item */

$layoutManipulator->add('header', 'root', 'header');
```

## Developer Reference

The following is a list of the key classes involved in the layout update loading mechanism, and their responsibilities:

 - [YamlDriver](../../../../Component/Layout/Loader/Driver/YamlDriver.php) - Loads layout update instructions from *YAML* file.
 - [PhpDriver](../../../../Component/Layout/Loader/Driver/PhpDriver.php) - *PHP* loader, takes *PHP* instructions and compiles them into the layout update.
 - [AbstractLayoutUpdateGenerator](../../../../Component/Layout/Loader/Generator/AbstractLayoutUpdateGenerator.php) - base class to implement generator for different formats.
 - [ConfigLayoutUpdateGenerator](../../../../Component/Layout/Loader/Generator/ConfigLayoutUpdateGenerator.php) - config based generator, now utilized by *YAML* loader, but may be reused for other formats (such as *XML*, *PHP arrays*) as well.

To implement a loader for a new format that is different the from supported ones, the [DriverInterface](../../../../Component/Layout/Loader/Driver/DriverInterface.php) interface should be implemented and registered in the loader as a known driver (add the `addDriver` *method call* for the `oro_layout.loader` service definition).
