Layout update
=============

Overview
--------

A **layout update** is a set of actions that should be performed with the **[layout](what_is_layout.md)** in order to
customize the page look depending on our needs. The **layout update** may be performed manually
(via `\Oro\Component\Layout\LayoutBuilder`) or collected by *Oro Platform* loaders automatically.

Loaders
-------

A loader is responsible for reading, preparing and caching the content of update file. Out of the box *Oro Platform* 
supports *PHP* and *YAML* loaders that load layout updates matched by file extension. Each of these loaders generates 
*PHP* classes in cache directory (`app/cache/{env}/layouts`). For production mode validation and generation of cache files 
is performed by *optional cache warmer* and could be enforced by running the shell command `app/console cache:warmup --env=prod`.

Yaml syntax
-----------

This section covers basic layout update file structure and syntax that could be extended for advanced usage.
The **layout update** file should have `oro_layout` as a root node and may consist of `actions` and `conditions` nodes.

### Actions

**Actions** -- array node with a set of actions to execute. Generally, each action will be compiled as a separate call
to corresponding method of `\Oro\Component\Layout\LayoutManipulatorInterface`.

Here is the list of available actions:

| Action name | Description |
|------- |-------------|
| `add` | Add a new item to layout |
| `addTree` | Add multiple items defined in tree hierarchy. [See expanded doc](#add-tree). |
| `remove` | Remove the item from layout |
| `move` | Change the layout item position. Could be used in order change parent item or ordering position. |
| `addAlias` | Add alias for the layout item. Could be used for backward compatibility. |
| `removeAlias` | Remove the alias previously defined for the layout item |
| `setOption` | Set the option value for the layout item |
| `removeOption` | Unset the option from the layout item |
| `changeBlockType` | Change block type of the layout item |
| `setBlockTheme` | Define theme file where renderer should look up for block templates |
| `clear` | Clear state of the manipulator. Basically prevent execution of all previously collected actions |

Action definition is a multidimensional array where the keys are **action name** prefixed by `@` sign, and the values are 
arguments that will be passed directly to proxied method call. Arguments could be sequentially ordered or an associative array.

**Example**
```yml
oro_layout:
    actions:
        - @add: # Sequential list
            - block_id
            - parent_block_id
            - block_type
        - @remove: # Named arguments
            id: content
```

Previous example will be simply compiled in following PHP code

```php
$layoutManipulator->add( 'block_id', 'parent_block_id', 'block_type' );
$layoutManipulator->remove( 'content' );
```

Optional parameters could be skipped in case when named arguments are used. In the following example, we skip optional argument 
`parentId` that will be set to default value automatically.

**Example**
```yml
oro_layout:
    actions:
        - @move:
            id:        block_id
            siblingId: sibling_block_id
```

#### AddTree action

It might be useful and more readable to add a set of blocks defined in the tree structure. For this purposes `addTree`
action was developed. It requires two nodes to be defined `items` and `tree`.

**Items** -- array node consists of block definitions where key will be utilized as **block id** for `@add` action.

**Tree** -- blocks hierarchy to be built. First node of the tree should be set to **block id** that will be treated as
parent block.

**Example**
```yml
oro_layout:
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

**Note:** tree definition could refer only to *items* that are declared in the same particular `@addTree` action, 
otherwise syntax error will occur. 

Leafs of the tree could be defined as sequentially ordered array items, but, please, take into account that *YAML* syntax
does not allow to mix both approaches in the same array node, so we recommend to use associative array syntax.

### Conditions

**Conditions** -- array node which contains conditions that must be satisfied for **layout update** to be executed.
As a simple example let's imagine that some set of actions should be executed only for a page that currently served to a mobile device.
The syntax of conditions declaration is very similar to *actions*, except that it should contain a single condition.
Special grouping conditions (such as `@tree`, `@or`, `@and`) could be utilized in order to combine multiple conditions.

**Example**
```yml
oro_layout:
    actions:
        ....
    conditions:
        - @and:
            - @lte: [$call_timeout, 30]
            - @gt:  [$call_timeout, 0]
```

**[Layout context](./layout_context.md)** could be accessed through the condition expressions by referencing to `$context` variable.

Please, refer to the [ConfigExpression component](../../../../Component/ConfigExpression/README.md) documentation for further detailed explanation.

PHP syntax
----------

Basically, *PHP* file update allows developer to write the update instructions directly. It's highly recommended to use it only in
case when other *config based* loaders do not allow to meet your specific requirements. Opening and closing *PHP* tags 
could be omitted. It's recommended to use opening tag and *PHPDoc* variable typehinting to get your IDE autocomplete working.

**Example**
```php
/** @var Oro\Component\Layout\LayoutManipulatorInterface $layoutManipulator */
/** @var Oro\Component\Layout\LayoutItemInterface $item */

$layoutManipulator->add('header', 'root', 'header');
```

Developer reference
-------------------

Here is a list of key classes involved in layout update loading mechanism and their responsibilities:

 - `\Oro\Bundle\LayoutBundle\Layout\Loader\YamlFileLoader` - Loads layout update instructions based on *YAML* config.
 - `\Oro\Bundle\LayoutBundle\Layout\Loader\PhpFileLoader` - *PHP* loader, takes *PHP* instructions and compiles it into layout update.
 - `\Oro\Bundle\LayoutBundle\Layout\Generator\AbstractLayoutUpdateGenerator` - base class to implement generator for new format.
 - `\Oro\Bundle\LayoutBundle\Layout\Generator\ConfigLayoutUpdateGenerator` - config based generator, now utilized by *YAML* loader,
    but may be reused for other formats (such as *XML*, *PHP arrays*) as well.

In order to implement loader for a format different form supported `\Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface` 
should be implemented, and added as a known loader to loaders chain (add `addLoader` *method call* 
for `oro_layout.loader.chain_loader` service definition).
