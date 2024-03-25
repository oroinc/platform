# ASTNodeWrapper

Source: [`oroexpressionlanguage/js/ast-node-wrapper.js`](../../public/js/ast-node-wrapper.js)

## Purpose

During parsing, an `ExpressionLanguage`[[?]](./expression-language.md) creates `ParsedExpression` which contains nodes that are structured as a tree.
When you work with the tree, you sometimes need to get the parent of a node, find all the nodes of a particular type in the subtree, etc.
Abstract Syntax Tree (AST) node wrapper is designed to make using of the tree more convenient.

Constructor gets a node and goes through its children. It recursively wraps each child node in the instance of `ASTNodeWrapper`.

## Methods and properties

The ASTNodeWrapper provides the following public methods:

- `attr` - Gets the attribute name and returns the value of the attribute of the origin node.
- `child` - Gets the index as a parameter and returns the corresponding child of the origin node.
- `instanceOf` - Gets one or more function-constructors provided in the parameter and checks if the origin node instance inherits a prototype property of constructor(s).
- `findAll` - Iterates through the tree and returns the array of wrappers that match the condition evaluated by the callback function passed in the parameter. The `findAll` function tests every tree node with a callback function and adds the node wrapper to the array only if the callback function returned true. 
- `findInstancesOf` - Gets the function-constructor provided in the parameter, iterates through the tree, identifies the nodes that are instances of the provided constructor, and returns the array of their wrappers.

Also, you can use `parent` property that contains either the link to the parent wrapper or a `null` value (when used with a root node). 

## How to use

Let us see how to use the `parsedExpression`. To create an instance of the wrapper, pass its nodes to the constructor.


````
var astNodeWrapper = new ASTNodeWrapper(parsedExpression.getNodes());
````

Then, use the `ASTNodeWrapper` instance for your purposes.

For example, you can:

- Get attribute `value` from the node: 

  ```
  var value = astNodeWrapper.attr('value');
  ```

- Get a first child:

  ```
  var firstChild = astNodeWrapper.child(0);
  ```

- Get a parent node of any node:

  *Note:* When used with the firstChild from the previous example, it returns the root `astNodeWrapper` node.

  ```
  var parent = firstChild.parent;
  ```

- Get an array of the nodes of the particular type:

  ```
  var someTypeNodes = astNodeWrapper.findInstancesOf(SomeType);
  ```
  
- Get array of all nodes with attribute `value` that equals `7`

  ```
  var nodes = astNodeWrapper.findAll(function(node) {
      return node.attr('value') === 7;
  });
  ```
