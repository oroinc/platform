Transition Forms
================

- Configuring
  - [Simple Example (filling the attribute)](#simple-example---filling-the-attribute)
  - [Reach Example (form_init)](#reach-example)
  - [Custom Form Type Example](#custom-form-type-example)
  - [A Form Reuse Recommendation](#recommendations)
- Transition Forms and Layouts

## Configuring
It is often happens, that for a business flow is not enough the data that present in the system to move flow farther 
automatically by pressing a single button.
So the user usually forced to provide additional data in UI forms for proceeding. It might be a few fields or complex 
entities form filling.

Workflow Transitions can be configured to handle custom data filled by a user by providing the form on UI before 
transition commit happens.
Here we will show you common ways to configure transition forms in a few examples.

### Simple Example - filling the attribute:
Suppose we have a workflow that handles only one required data input from a user.

```YML
workflows:
    greeting_flow:
        entity: Oro\Bundle\UserBundle\Entity\User
        entity_attribute: user
        defaults: { active: true }
        attributes:
            the_message:
                type: string
        steps:
            congratulated:
                allowed_transitions: [ congratulate_with ]
        transitions:
            congratulate_with:
                is_start: true
                step_to: congratulated
                form_options:
                    attribute_fields:
                        the_message:
                            options:
                                constraints:
                                    - NotBlank: ~
                destination_page: view
                transition_definition: message_definition
        transition_definitions:
            message_definition:
                actions:
                    - '@flash_message': {message: $.data.the_message, type: success}
                    
```
On above is a simple working example of cycled workflow with one step and one transition.
On the transition `congratulate_with` we should force user to fill a text input `the_message` field that corresponds to 
our configured workflow `attribute`. Also the field is required by `constraints` in form `attribute_fields` `options`. 
Then we can submit it as a transition payload.
After form send we should see a `@flash_message` with the text we prompt on the dialog (the default for transition 
`display_type`) that is flashed on the entity view page.


### Reach Example:
 **Custom types and `form_init`:**
```YML
workflows:
    user_update_flow:
        entity: Oro\Bundle\UserBundle\Entity\User
        entity_attribute: user
        defaults: { active: true }
        attributes:
            my_message:
                type: string
            my_dote:
                type: object
                options:
                    class: DateTime
        steps:
            congratulated:
                allowed_transitions: [ congratulate_with ]
        transitions:
            congratulate_with:
                is_start: true
                step_to: congratulated
                form_options:
                    form_init:
                        - '@create_object':
                            class: \DateTime
                            attribute: $.data.my_date
                            parameters: ['tomorrow']
                    attribute_fields:
                        my_message:
                            options:
                                constraints:
                                    - NotBlank: ~
                        my_date:
                            form_type: my_date_picker #here your custom date picker
                destination_page: view
                transition_definition: message_definition
        transition_definitions:
            message_definition:
                actions:
                    - '@flash_message': {message: $my_message, type: success}
                    
```
Now lets pretend that we need more complex form to be filled by a user who performing a transition.
So we need to specify fields for the data that we need. But before that, lets prepare some of data to be show for a user in `form_init`.

**The `form_init`:**

The `form_init` node that is under `form_options`. It is an 
[oro action](../../../../../../Component/Action/Resources/doc/actions.md) that will be performed before form render.
Here you can prepare your data before form render. At sample configuration we are creating new `\DateTime` object that is pre configured to tomorrow time.
So that on our custom `"my_date_picker"` type we will se the day after today predefined on form.


### Custom Form Type Example:

Also there is possibility to use your custom form type for a whole transition handling.
We bring here a simple working example of commerce pseudo flow:

```YML
workflows:
    quote_update_circular:
        entity: Oro\Bundle\CustomerBundle\Entity\CustomerUser
        entity_attribute: customer_user
        defaults: {active: true}
        attributes:
           quote: #here we will store our form data result
               type:  entity
               options:
                   class: 'Oro\Bundle\SaleBundle\Entity\Quote'
        steps:
            quote:
                allowed_transitions:
                    - transit_quote
        transitions:
            transit_quote:
                step_to: quote
                is_start: true
                transition_definition: quote_update_definition
                display_type: dialog
                form_type: 'Oro\Bundle\SaleBundle\Form\Type\QuoteType' #define a custom form type to use for transit
                form_options:
                    configuration: #define configuration for the custom form type
                        handler: 'default' #which handler should process the from (custom form transition handler)
                        template: 'OroSaleBundle:Quote:update.html.twig' #our complex form page template
                        data_provider: 'quote_update' #template context data provider that will provide data for the template
                        data_attribute: 'quote' #attribute to store form data and get from
                    form_init: #here we will prepare our form
                        - '@tree':
                            conditions: #if no quote is defined in our worfklow data ... ->
                                '@empty': [$quote]
                            actions:
                                - '@create_object': #.. -> we will create it
                                    class: Oro\Bundle\SaleBundle\Entity\Quote
                                    attribute: $.data.quote # and set to our data_attribute defined in configuration
                                    parameters: ~
                        - '@assign_value': #add some more preparation of the form data object below by WF entity data
                            attribute: $.data.quote.customerUser
                            value: $customer_user
                        - '@assign_value':
                            attribute: $.data.quote.customer
                            value: $customer_user.customer
                    attribute_fields: ~ #attribute fields should be ommited as we use totally custom form type
        transition_definitions:
            quote_update_definition:
                actions:
                    - '@flash_message':
                        message: 'Workflow transited. Entity updated!'
                        type: 'success'
                    - '@redirect': {route: 'oro_sale_quote_index'}
```
Here, above, workflow configured which creates a new Quote from the start on Customer User page and perform updates for 
the Quote it circularly in each transition, because it brings us back to the same step.

Now lets look at configuration specific moments.
To use your custom form type that replaces default transition form you must set the type in `form_type` option to your custom one. 
**Note** That FQCN should be used as the value for *form_type* when defining custom form type and this form must be resolvable by "Form Registry".  
Together with that, you must specify correct `configuration` for the type customization (`handler`, `template`, `data_provider`, `data_attribute` options).
Here we have `Oro\Bundle\SaleBundle\Form\Type\QuoteType` from type. But for proper handling of that complex form type, we need to specify additional options in
`form_options.configuration` node. Those are:
- `handler` - an alias of registered by the tag `oro_form.registry.form_handler` service. You can use the default one by just passing `'default'`. 
See more about form update handler in corresponding [OroFormBundle doc page](../../../../../FormBundle/Resources/doc/reference/update_handler.md).
- `template` - the name of a template that should be used for the custom form, default value is `OroWorkflowBundle:actions:update.html.twig` and this template can be used as starting point for customizations. 
**Note**: it should be extended from `OroUIBundle:actions:update.html.twig` for compatibility with transition form page (usually all Oro update templates do so).
- `data_provider` - an alias of registered by tag `oro_form.form_template_data_provider` service that implements `Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface`.
It should return all necessary data for specified template as controllers usually do.
- `data_attribute` - the name of data attribute where form data payload should be taken from by workflow engine to pass into form and putting to as result of handling.

#### A Form Reuse Recommendation

In general, ***the main recommendation*** when you creating a new entity management (entity controller) while developing: 
The best approach would be to use our `Oro\Bundle\FormBundle\Model\UpdateHandler::update` method functionality. 
So that if you encapsulate your logic to proper parts of form handling process then you should be able to create a 
workflow with the custom form type easily. As custom form workflow transition handling is based on reusing those parts in transition configuration.   

## Transition Forms and Layouts

For layout based sites you can use the [Layout Update](../../../../../LayoutBundle/Resources/doc/layout_update.md) 
functionality to the UI customization capabilities of a transition form.

First of all, you need to be familiar with the [layout update](../../../../../LayoutBundle/Resources/doc/layout_update.md) type of interface build so that you should proceed further to manage layout based transition forms there.

## Imports for new Controllers
There are several major imports that can handle next types of transition forms:
 - [oro_workflow_transition_form](../../../views/layouts/default/imports/oro_workflow_transition_form) - for regular transition
 - [oro_workflow_start_transition_form](../../../views/layouts/default/imports/oro_workflow_transition_form) - for start transition
 
Please consider adding them to your custom transition form controller.

## The context`s data
The following layout context variables are available for transition forms:
- `workflowName` - the name of a workflow
- `transitionName` - the name of a transition
- `transitionFormView` - the form view instance (used in rendering)
- `transition` - the instance of [Transition](../../../../Model/Transition.php) class that current transit corresponds to
- `workflowItem` - the instance of [WorkflowItem](../../../../Entity/WorkflowItem.php) - current workflow record representation
- `formRouteName` - the route that should be populated by LayoutTransitionContext processor in [TransitionContext](../../../../Processor/Context/TransitionContext.php)

### Limitations
A workflow transition form **does not have layout form provider**. So you cannot reuse it in other layouts.
It is a known drawback, but transition process is quite complex, and transition form reusage could make data dependency management quite complicated. So this is more a favor that drawback ;)

Also see [custom form type configuration of workflow](#custom-form-type-example).
