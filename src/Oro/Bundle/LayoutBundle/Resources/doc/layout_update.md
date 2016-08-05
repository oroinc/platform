Layout update
=============

Overview
--------

A **layout update** is a set of actions that should be performed with the **[layout](what_is_layout.md)** in order to
customize the page look depending on our needs. The **layout update** may be performed manually
(via [LayoutBuilder](../../../../Component/Layout/LayoutBuilder.php)) or collected by the *Oro Platform* loaders automatically.

Loaders
-------

A loader is responsible for reading, preparing and caching the content of update file. Out of the box *Oro Platform* 
supports *PHP* and *YAML* loaders that load layout updates matched by file extension. Each of these loaders generates 
*PHP* classes in cache directory (`app/cache/{env}/layouts`). For production mode validation and generation of cache files 
is performed by *optional cache warmer* and could be enforced by running the shell command `app/console cache:warmup --env=prod`.

Yaml syntax
-----------

This section covers basic layout update file structure and syntax that can be extended for advanced usage.
The **layout update** file should have `layout` as a root node and may consist of `actions` and `conditions` nodes.

### Actions

**Actions** -- array node with a set of actions to execute. Generally, each action will be compiled as a separate call
to corresponding method of [LayoutManipulatorInterface](../../../../Component/Layout/LayoutManipulatorInterface.php).

Here is the list of available actions:

| Action name | Description |
|------- |-------------|
| `add` | Add a new item to layout |
| `addTree` | Add multiple items defined in tree hierarchy. [See expanded doc](#addtree-action). |
| `remove` | Remove the item from layout |
| `move` | Change the layout item position. Could be used in order change parent item or ordering position. |
| `addAlias` | Add alias for the layout item. Could be used for backward compatibility. |
| `removeAlias` | Remove the alias previously defined for the layout item |
| `setOption` | Set the option value for the layout item |
| `appendOption` | Add a new value in addition to existing values of an option of the layout item |
| `subtractOption` | Remove existing value from an option of the layout item |
| `replaceOption` | Replace one value with another value of an option of the layout item |
| `removeOption` | Unset the option from the layout item |
| `changeBlockType` | Change block type of the layout item |
| `setBlockTheme` | Define theme file where renderer should look up for block templates |
| `clear` | Clear state of the manipulator. Basically prevent execution of all previously collected actions |

Action definition is a multidimensional array where the keys are **action name** prefixed by `@` sign, and the values are 
arguments that will be passed directly to proxied method call. Arguments can be passed as a sequential list or an associative array.

**Example**

```yaml
layout:
    actions:
        - @add: # Sequential list
            - block_id
            - parent_block_id
            - block_type
        - @remove: # Named arguments
            id: content
```

Previous example will be simply compiled in the following PHP code

```php
$layoutManipulator->add( 'block_id', 'parent_block_id', 'block_type' );
$layoutManipulator->remove( 'content' );
```

Optional parameters can be skipped in case when named arguments are used. In the following example, we skip the optional argument
`parentId` that will be set to the default value automatically.

**Example**

```yaml
layout:
    actions:
        - @move:
            id:        block_id
            siblingId: sibling_block_id
```

#### AddTree action

It might be useful and more readable to add a set of blocks defined in a tree structure. For this purposes `addTree`
action has been developed. It requires two nodes to be defined - `items` and `tree`.

**Items** -- an array node consisting of block definitions where key will be treated as **block id** for `@add` action.

**Tree** -- blocks hierarchy to be built. First node of the tree should be set to **block id** that will be treated as the
parent block.

**Example**

```yaml
layout:
    actions:
        - @addTree:
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

**Note:** a tree definition could refer only to the *items* that are declared in the same particular `@addTree` action,
otherwise a syntax error will occur.

Leafs of the tree can be defined as sequentially ordered array items, but, please, take into account that *YAML* syntax
does not allow to mix both approaches in the same array node, so we recommend to use the associative array syntax.

### Conditions

**Conditions** -- an array node which contains conditions that must be satisfied for **layout update** to be executed.
As a simple example, let's imagine that some set of actions should be executed only for a page that is currently served to a mobile device.
The syntax of conditions declaration is very similar to *actions*, except that it should contain a single condition.
Special grouping conditions (such as `or`, `and`) could be utilized in order to combine multiple conditions.

**Example**

```yaml
layout:
    actions:
        ....
    conditions: 'context["user_agent"].getMobile() == true or context["navbar_position"] == "top"'
```

**[Layout context](./layout_context.md)** could be accessed through the condition expressions by referencing to `$context` variable.

Please, refer to the [Symfony expression syntax](http://symfony.com/doc/current/components/expression_language/syntax.html) documentation for further detailed explanation.

PHP syntax
----------

Basically, a *PHP* layout update allows developer to write the update instructions directly. It's highly recommended to use it only in
case when other *config based* loaders do not satisfy your specific requirements. Opening and closing *PHP* tags
can be omitted. It's recommended to use opening tag and *PHPDoc* variable typehinting to get your IDE autocomplete working.

**Example**

```php
/** @var Oro\Component\Layout\LayoutManipulatorInterface $layoutManipulator */
/** @var Oro\Component\Layout\LayoutItemInterface $item */

$layoutManipulator->add('header', 'root', 'header');
```

Developer reference
-------------------

Here is a list of key classes involved in the layout update loading mechanism and their responsibilities:

 - [YamlDriver](../../../../Component/Layout/Loader/Driver/YamlDriver.php) - Loads layout update instructions from *YAML* file.
 - [PhpDriver](../../../../Component/Layout/Loader/Driver/PhpDriver.php) - *PHP* loader, takes *PHP* instructions and compiles them into the layout update.
 - [AbstractLayoutUpdateGenerator](../../../../Component/Layout/Loader/Generator/AbstractLayoutUpdateGenerator.php) - base class to implement generator for different formats.
 - [ConfigLayoutUpdateGenerator](../../../../Component/Layout/Loader/Generator/ConfigLayoutUpdateGenerator.php) - config based generator, now utilized by *YAML* loader, but may be reused for other formats (such as *XML*, *PHP arrays*) as well.

In order to implement a loader for a new format different from supported ones, the [DriverInterface](../../../../Component/Layout/Loader/Driver/DriverInterface.php)
interface should be implemented and registered in the loader as a known driver (add `addDriver` *method call*
for `oro_layout.loader` service definition).
