# OroActionBundle Documentation

## Table of Contents

 - [ActionBundle Glossary](#actionbundle-glossary)
 - [Console commands](#console-commands)
 - [The Dependency Injection Tags](./dependency_injection_tags.md)

  On top of common [Action Component](/src/Oro/Component/Action/Resources/doc/actions.md) and OroPlatform in general, **ActionBundle** provides a way to bring more complex solutions in ORO based projects with reusable components of configuration. 
  
  Those components are:
  
  **Buttons** - a user interface component that helps deliver custom actions for user interaction;
  
  **Operations** - configured user interaction elements (buttons, links or even further: forms, pages) with customized execution logic;
  
  **Action Groups** - complex business logic sets of backend actions grouped together under the named configuration nodes.


## ActionBundle Glossary

  * [**Buttons**](./buttons.md) - provide a way to demonstrate any kind of actions (operations, for example) to UI for a proper context through specific [ButtonsProviderExtension](../../Extension/ButtonProviderExtensionInterface.php) together with [Buttons](../../Button/ButtonInterface.php) matched by a context.

  * [**Operation**](./operations.md) - one of the main components in ActionBundle that handles information about a specific operation logic, how and when a UI element is displayed, the reaction it provides, and how to aggregate the data retrieved from a user (usually through a form) into execution unit values and launch configured *Actions* afterwards.

  The operation definition contains the most important information, such as operation related entity classes ('Acme\Bundle\DemoBundle\Entity\MyEntity'), or routes ('acme_demo_myentity_view'), or datagrids ('acme-demo-grid').
The operation can be enabled or disabled. Other fields of the operation contain information about its name, extended options, an order of displayed buttons. For more options please refer to [Operation Configuration](./operations.md#operation-configuration).
     
  * [**ActionGroup**](./action-groups.md) - another main component in ActionBundle. A named group of actions with entry `parameters` (required or optional, typed or not) and conditions. 
  
  *Action groups* can be used not only from an operation but within the workflow processes and in any part of the OroPlatform configuration nodes that understand [Actions](/src/Oro/Component/Action/Resources/doc/actions.md).
A special `@run_action_group` action is designed to run a group of actions as a single one. (For more information please refer to [*ActionGroup* configuration](./action-groups.md#actiongroup-configuration) and [`@run_action_group` action](./actions.md#run_action_group)).

  * [**Condition**](./conditions.md) - defines whether *Operation* or *ActionGroup* is allowed. Conditions use [ConfigExpression](/src/Oro/Component/ConfigExpression/README.md) syntax and can be nested within each other. For more information regarding ActionBundle Conditions, how to create and use them, please refer to [this page](./conditions.md).

  * [**Actions**](./actions.md) - simple functional blocks (that are described in Action Component). They can be used in *ActionGroups* or *Operations* to implement the preparation logic before *conditions*, to retrieve rendering data, to initialize and execute the logic afterwards.
  
  *Operations* contain the following *actions*: **Preactions** (`preactions`), the **Form Init** actions (`form_init`), and **Actions** themselves with the functions of Action Component.
The difference between them is that `preactions` are executed before the operation button rendering, though the `form_init` actions are executed before form display. Actions can be used to perform any operations with data in their context (called Action Data) or other entities.

  * **Definition** - a part of *Operation* or *ActionGroup* that contains the configuration of the component itself and describes its behavior.

* **Attribute** - an entity that represents a value (mostly in *Operation*) and is used to render a field value in a step of a form. The attribute knows about its type (string, object, entity etc.) and additional options.
The attribute contains a name and label as additional parameters.

## Console commands


#### oro:debug:action

This command displays current actions for an application.

```
  oro:debug:action [<name>]
  oro:debug:action

Arguments:
  name (optional): An action name
```

##### Usage

- Displays a list of current actions `php bin/console oro:debug:action`;
- Shows a full description `php bin/console oro:debug:action [<name>]`.

#### oro:debug:condition

This command displays current conditions for an application.

```
  oro:debug:condition [<name>]

Arguments:
  name (optional): A condition name
```

##### Usage

- Displays list of all conditions `php bin/console oro:debug:condition`;
- Shows a full description `php bin/console oro:debug:condition [<name>]`.
