Layout update
=============

Overview
--------

**Layout update** is a set of actions that should be performed with **layout** in order to make the page look depends on our needs.
Based on context where you use **layout** update may be performed manually (via `\Oro\Component\Layout\LayoutBuilder`) or may be collected
by *Oro Platform*'s loaders automatically.

Loaders
-------

Loader is responsible for reading, preparing and caching the content of update files. Out of the box *Oro Platform* 
 supports *PHP* and *YAML* loaders that load layout updates matched by file extension. Each of this loaders reads a file from 
 path configured outside and generates *PHP* class in cache directory (`app/cache/{env}/layouts`). Validation and generation of cache files 
 for production mode performed in *optional* cache warmer and could be enforced by running shell command 
 `app/console cache:warmup --env=prod`
  
In order to implement loader for format different form supported  
`\Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface` should be implemented, and added as known loader to loaders 
chain (add `addLoader` *method call* for `oro_layout.loader.chain_loader` service definition).

Yaml syntax
-----------

This section covers basic layout update file structure and syntax, that could be extended for advanced usage.
**Layout update** file should has `oro_layout` as root node and may consist `actions` and `conditions` nodes.

### Actions

**Actions** -- array node with a set of actions to execute. Generally each action will be compiled as separate call
of some method of `\Oro\Component\Layout\LayoutManipulatorInterface`.

Here is the list of available actions:

| Action name | Description |
|------- |-------------|
| `add` | Add new item to layout |
| `addTree` | Add multiple blocks defined in tree hierarchy. [See expanded doc](#add-tree). |
| `remove` | Remove the item from layout |
| `move` | Change the layout item position. Could be used in order to move elements between containers. |
| `addAlias` | Add alias for the layout item. Could be used for backward compatibility. |
| `removeAlias` | Remove the alias previously defined for the layout item |
| `setOption` | Set the option value for the layout item |
| `removeOption` | Unset the option from the layout item |
| `changeBlockType` | Change block type of the layout item |
| `setBlockTheme` | Define theme file where renderer should look up for block templates |
| `clear` | Clear state of the manipulator. Basically prevent execution of all previously collected actions |

Action definition is array that consist **action name** prefixed by `@` sign as array key, and array of arguments that 
will be passed directly to proxied method call. Arguments could be sequential ordered array or be associative parameters list.

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

Previous example will be simple compiled in following PHP code

```php
        $layoutManipulator->add( 'block_id', 'parent_block_id', 'block_type' );
        $layoutManipulator->remove( 'content' );
```

Optional parameters could be skipped in case named arguments are used. For example, action `move` requires first argument 
`id` to be passed, so in order to execute **move** action with `id` and `siblingId` optional argument `parentId` will be 
set to default value automatically.

**Example**
```yml
oro_layout:
    actions:
        - @move:
            id:        block_id
            siblingId: sibling_block_id
```

#### AddTree action

It's might be useful and more readable to add a set of blocks defined in tree structure. For this purposes `addTree` action was developed.
It requires two nodes to be defined `items` and `tree`.

**Items** -- array node consist of block definitions where key will be utilized as `id` for `@add` action.

**Tree** -- blocks hierarchy to be built. First node of the tree should be set to **block id** that will be utilized as parent block.

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

**Note:** tree definition could refer to *items* that declared in the same particular `@addTree` action, otherwise syntax error will occur. 

Leafs of the tree could be defined as sequential ordered array items, but please take into account that *YAML* syntax does not allow
to mix both approaches under particular array node, so we recommend to use associative array syntax.

### Conditions

**Conditions** -- array node which contains conditions that must satisfy to allow the **layout update** be executed.
As a simple example let's imagine that some set of actions should be executed only for page that currently served to a mobile device.
The syntax of condition declaration is very similar to *actions* except that the node it self should contain single condition, 
and special grouping conditions(such as `@tree`, `@or`, `@and` could be utilized in order to combine multiple conditions.

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

**Layout context** could be accessed through the condition expressions via referencing to `$context` variable.

Please refer to the [ConfigExpression component](../../../../Component/ConfigExpression/README.md) documentation for further detailed explanation.

PHP syntax
----------

Basically *PHP* file update allow developer to write the code of the layout update by himself. It's highly recommended to use it only in
case if another *config* based loaders do not allow to meet some specific need. 
Open and close *PHP* tags could be omitted. It's recommended to use open tag and *PHPDoc* variable typehints to get your IDE autocomplete works.

**Example**
```php
/** @var Oro\Component\Layout\LayoutManipulatorInterface $layoutManipulator */
/** @var Oro\Component\Layout\LayoutItemInterface $item */

$layoutManipulator->add('header', 'root', 'header');
```
