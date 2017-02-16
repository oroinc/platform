Transition Forms
================

It is often happens, that for a business flow is not enough the data that present in the system to move flow farther 
automatically by pressing a single button.
So the user usually forced to provide additional data in UI forms for proceeding. It might be a few fields or complex 
entities form filling.

Workflow Transitions can be configured to handle custom data filled by a user by providing the form on UI before 
transition commit happens.
Here we will show you common ways to configure transition forms in a few examples.

Simple Example - filling the attribute:
--------------------------------------
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
On the transition `congratulate_with` we should enter a text input `the_message` that corresponds to our configured 
workflow `attribute` and that field is required by `constraints` in form `attribute_fields` `options`. 
Then we can submit it as a transition payload.
After form send we should see a `@flash_message` with the text we prompt on the dialog (the default for transition 
`display_type`) that is flashed on the entity view page.

Reach Example - filling complex form and types:
----------------------------------------------
```YML
workflows:
    reach_greeting_flow:
        entity: Oro\Bundle\UserBundle\Entity\User
        entity_attribute: user
        defaults: { active: true }
        attributes:
            my_message:
                type: string
            the_note:
                type: string
            the_date:
                type: datetime
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
                            attribute: $.data.the_date
                            parameters: ['now']
                    attribute_fields:
                        my_message:
                            options:
                                constraints:
                                    - NotBlank: ~
                        my_date:
                            form_type: my_date_picker
                            
                        
                destination_page: view
                transition_definition: message_definition
        transition_definitions:
            message_definition:
                actions:
                    - '@flash_message': {message: $.data.my_message, type: success}
                    
```
Now lets pretend that we need more complex form to be filled by a user who performing a transition.
So we need to specify fields for the data that we need. But before that, lets prepare some of data to be show for a user.

Focus on the `form_init` node that is under `form_options`. It is an 
[oro action](../../../../../../Component/Action/Resources/doc/actions.md) that will be performed before form render.

Custom Form Type Example:
------------------------
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
            transit_quote: #start transition perform update of quote on which transition was invoked
                step_to: quote
                is_start: true
                transition_definition: quote_update_definition
                frontend_options:
                    icon: 'fa-bolt'
                display_type: dialog
                form_options:
                    attribute_fields: ~
                    form_init:
                        - '@tree':
                            conditions:
                                '@empty': [$quote]
                            actions:
                                - '@create_object':
                                    class: Oro\Bundle\SaleBundle\Entity\Quote
                                    attribute: $.data.quote
                                    parameters: ~
                        - '@assign_value':
                            attribute: $.data.quote.customerUser
                            value: $customer_user
                        - '@assign_value':
                            attribute: $.data.quote.customer
                            value: $customer_user.customer
                page_form_configuration:
                    handler: 'default' #which handler should process the from (custom form transition handler)
                    template: 'OroSaleBundle:Quote:update.html.twig' #form page template
                    data_provider: 'quote_update' #template context data provider
                    data_attribute: 'quote' #attribute to store form data and get from
                form_type: 'oro_sale_quote' #the form type to use for transit
        transition_definitions:
            quote_update_definition:
                actions:
                    - '@flash_message':
                        message: 'Workflow transited. Entity updated!'
                        type: 'success'
                    - '@redirect': {route: 'oro_sale_quote_index'}
```
Here, above, workflow configured which creates a new Quote from the start and perform updates for it circularly in 
each transition, because it brings us back to the same step.

Now lets look at configuration specific moments.
To use your custom form type that replaces default transition form you must set the type in `form_type` option of `form_options` node.
Here we have `oro_sale_quote` from type. But for proper handling of that complex form type, we need to specify additional options in
`page_form_configuration` node. Those are:


