OroActionBundle Documentation
=============================

  On top of common [Action Component](/src/Oro/Component/Action/Resources/doc/actions.md) and Oro Platform in general, 
**ActionBundle** provide a way to bring more complex solutions in ORO based projects with reusable parties of
configuration. 
  Those parties are:
  
  **Operations** - configured user interaction elements (buttons, links or even further: forms, pages) with customizable
execution logic;
  
  **Action Groups** - complex business logic sets of backend actions, grouped together under named configuration nodes.


ActionBundle Glossary
---------------------

  * [**Operation**](./operations.md) - one of the main models in ActionBundle that handle an information about
specific operation logic, how and when to display UI element, what reaction should it provide and how to aggregate data
retrieved from a user (usually through a form) into execution units values and launch configured *Actions* after all.
  Operation definition contains the most important information like operation related entity classes (f.e.
'Acme\Bundle\DemoBundle\Entity\MyEntity') or routes ('acme_demo_myentity_view') or datagrids ('acme-demo-grid').
An *Operation* can be enabled or disabled. Other fields of the operation contain information about its name, extended 
options, an order of display buttons.
 More options see in [Operation Configuration](#operation-configuration).
 
  * [**ActionGroup**](./action-groups.md) - another one of the main models in ActionBundle. A named bunch of Actions with entry
`parameters` (required or optional, typed or not) and conditions. 
  *Action groups* can be used (e.g. called) not only from an Operation but within Workflow processes and even more - 
in any part of ORO Platform configuration nodes that understands [Actions](/src/Oro/Component/Action/Resources/doc/actions.md).
Special `@run_action_group` action is designed for purpose to run bunch of actions as a single one. (See more about
[*ActionGroup* configuration](#action-group-configuration) and [`@run_action_group` action](./actions.md#run_action_group)).

  * [**Condition**](./conditions.md) - defines whether specific *Operation* or *ActionGroup* is allowed. Conditions can
be nested and uses [ConfigExpression](/src/Oro/Component/ConfigExpression/README.md) syntax. Se more about ActionBundle
Conditions, how to create and use them at [this page](./conditions.md).

  * [**Actions**](./actions.md) - simple functional blocks (that are described in Action Component) they can be used 
in *ActionGroups* or *Operations* to perform: logic of preparation before conditions, retrieving rendering data, forms 
initialization and execution logic after that.
  For *Operations* that *actions* are: **Pre Actions** (`preactions`), **Form Init** actions (`form_init`) and, finally,
**Actions** itself with all power of Action Component.
The difference between them is that `preactions` are executed before Operation button render and `form_init` actions
are executed before form display. Actions can be used to perform any operations with data in their context
(called Action Data) or other entities.

  * **Definition** - part of the any main model (*Operation* or *ActionGroup*) that contains the configuration of the
model itself that describes all behavior, ready to use with its named instance.

* **Attribute** - an entity that represents a value (mostly in Operation), used to render field value in a step of a form.
Attribute knows about its type (string, object, entity etc.) and additional options.
The Attribute contains name and label as additional parameters.
