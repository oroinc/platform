# Oro Layout Component

Oro Layout component defines the object-oriented presentation of a page structure and provides tools to build, manage, and render the page.

**IMPORTANT**: This component is not finished yet.

`Oro Layout Component` provides tools for:

- defining the elements of the layout (blocks) that can be used to build different types of layouts, including HTML, XML, etc.
- managing the layout structure and themes.

## Introduction

Before you begin working with layout, make sure you are familiar with the following key `Oro Layout Component` concepts:

 - A layout is a set of widgets that are arranged in a hierarchical structure, where a widget is a logical block that contains certain content.
 
 - Neither the layout nor a widget know how they should be converted (or in other words, rendered) into the output string (e.g. HTML). To make this conversion, we use renderers, for example a renderer based on TWIG templates.
  
 - The root idea and implementation of Oro layouts are mostly similar to the Symfony's [Forms](http://symfony.com/doc/current/book/forms.html) component. However, the difference is that layouts support only one-way data flow. It means that content of layout widgets can be rendered based on data, but layouts cannot be used to process user-submitted data.
 
 - There are two core layout widgets, the **block** and the **container**:
 
	- The block represents a widget which cannot contain any other widgets. Examples of blocks can be a label, a chart, a grid, etc.
	
	- The container is a structural widget which can contain other widgets. Examples of containers can be a header, a side bar, a page body, etc.

## High-Level Architecture

![The high-level architecture](./Resources/doc/high_level_architecture.png "The high-level architecture of Oro Layout component")

The Oro Layout component includes three main layers:

 - The **Foundation** layer provides the main architectural components used to build a layout.
 - The **Extensions** layer extends the layout with more useful features, such as loading widgets from the DI container and working with layout inheritance.
 - The **View** layer provides components responsible for translating a layout into rendering HTML, XML, or any other format.

The Oro Layout component provides several pluggable extensions out of the box:

 - The **Core** extension provides all widget definitions (called block types) implemented by the component.
 - The **DI** extension adds support for the Symfony's [Dependency Injection](http://symfony.com/doc/current/components/dependency_injection/introduction.html) component.
 - The **Themes** extensions allows to build layouts based on other layouts and provide flexible configuration of layouts.


## Layout Life Cycle

The layout usually goes through the stages outlined in the following table.

| Stage | Description |
|------ |-------------|
| **Create the layout context** | The layout context should be created manually by calling the constructor of the [LayoutContext](./LayoutContext.php) class. If necessary, additional variables can be added to the context at this stage. |
| **Configure the layout context** | At this stage, the `configureContext` method of all registered [layout configurators](./ContextConfiguratorInterface.php) is called. |
| **Resolve the layout context** | After this stage, adding new variables to the layout context is not possible, but it is still possible to change the value of existing variables. |
| **Add `root` block** | The root block should be added manually to start the execution of the layout update chain. See the description of the next stage for more details. | 
| **Execute layout updates** | The layout updates are linked to the layout blocks, so they are executed after a block is added to the layout. If a block is not specified for an layout update, it is linked to the root block. |
| **Build blocks** | A block hierarchy is build starting from a parent block. The `buildBlock` method of the base block type is called first, then the `buildBlock` method of all registered extensions of the base block type is called; next the `buildBlock` method of the inherited block type and its extensions are called, etc. |
| **Build block views** | A block view hierarchy is build starting from a parent block. The `buildView` method of the base block type is called first, then the `buildView` method of all registered extensions of the base block type is called; next the `buildView` method of the inherited block type and its extensions are called, etc. The `buildView` method is called before the child views are built, so it is not possible to access child views there. |
| **Finish building of block views** | First, a parent view finishes building. The `finishView` method of the base block type is called first, then the `finishView` method of all registered extensions of the base block type is called; next the `finishView` method of the inherited block type and its extensions are called, etc. The `finishView` method is called after the child views are built, but before the child views finish building. |
| **Render the layout** | the layout rendering is the same as in Symfony Forms. See [How to Customize Form Rendering](http://symfony.com/doc/current/cookbook/form/form_customization.html) for more details. |

The following example illustrates how a simple layout can be build.

```php
$context = new LayoutContext();
$context->getResolver()
	->setRequired(['some_variable']);
$context->set('some_variable', 'some_value');

$layoutFactory = Layouts::createLayoutFactory();
$layout = $layoutFactory->createLayoutBuilder()
	->add('root', null, 'root')
	->add('header', 'root', 'header')
	->add('logo', 'header', 'logo', ['title' => 'Hello World!'])
	->getLayout($context);

echo $layout->render();
```

Also you can render only layout subtree instead of full tree.
For this you should set `rootId` argument in `LayoutBuilder::getLayout`.

```php
$layout = $layoutFactory->createLayoutBuilder()
	...
	->getLayout($context, 'some_block_id');
echo $layout->render();
```

To improve your understanding of how the layout works, study the [Layouts](./Layouts.php) class, the `getLayout` method of the [LayoutBuilder](./LayoutBuilder.php) class and the [BlockFactory](./BlockFactory.php) class. pay close attention to the `postExecuteAction` method of the[DeferredLayoutManipulator](./DeferredLayoutManipulator.php) class and the [LayoutRegistry](./LayoutRegistry.php) class.


## Developer Reference

The following is the list of the most important classes of the Oro Layout component:

 - [LayoutManager](./LayoutManager.php) is the main entry point to the Oro Layout component.
 - [Layouts](./Layouts.php) is the static helper that can be used if a dependency injection container is not used in your application.
 - [Layout](./Layout.php) represents a layout which is ready to be rendered.
 - [LayoutBuilder](./LayoutBuilder.php) provides a set of methods to build the layout.
 - [LayoutRegistry](./LayoutRegistry.php) holds all layout extensions.
 - [RawLayout](./RawLayout.php) represents a storage for all layout data, including a list of items, hierarchy of items, aliases, etc. This is an internal class and usually you do not need to use it outside of the component.
 - [RawLayoutBuilder](./RawLayoutBuilder.php) provides a way to build the layout data storage. This is an internal class and usually you do not need to use it outside of the component.
 - [DeferredLayoutManipulator](./DeferredLayoutManipulator.php) allows to construct a layout without worrying about the order of method calls.
 - [BlockTypeInterface](./BlockTypeInterface.php) provides an interface for all block types.
 - [AbstractType](./Block/Type/AbstractType.php) can be used as the base class for all **block** block types.
 - [AbstractContainerType](./Block/Type/AbstractContainerType.php) can be used as the base class for all **container** block types.
 - [BlockFactory](./BlockFactory.php) implements the logic for building layout blocks and their views.
 - [DataProviderDecorator](./DataProviderDecorator.php) allows to calls methods with pre-defined prefix.
 - [ThemeExtension](./Extension/Theme/ThemeExtension.php) loads layout updates.
 - [ImportVisitor](./Extension/Theme/Visitor/ImportVisitor.php) loads imports.
