Update Handler
==============

Form Handler Events
-------------------

Default form handler `Oro\Bundle\FormBundle\Model\FormHandler` triggers events that allow developer to modify processing data
or even stop form processing.

There are two type of events:

- **FormProcessEvent** (`Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent`) - triggered during form processing,
can stop further form processing via `interruptFormProcess` method.
- **AfterFormProcessEvent** (`Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent`) - triggered after form 
processing during saving of form data.

And there are four events triggered in form handler:

- **BEFORE_FORM_DATA_SET** (`Oro\Bundle\FormBundle\Event\FormHandler\Events::BEFORE_FORM_DATA_SET`) - uses 
FormProcessEvent, triggered in the very beginning of form processing right before input data set to form instance.
- **BEFORE_FORM_SUBMIT** (`Oro\Bundle\FormBundle\Event\FormHandler\Events::BEFORE_FORM_SUBMIT`) - uses FormProcessEvent,
triggered only for valid form methods (POST or PUT) before request submitting to form instance.
- **BEFORE_FLUSH** (`Oro\Bundle\FormBundle\Event\FormHandler\Events::BEFORE_FLUSH`) - uses AfterFormProcessEvent,
triggered right before flushing of object manager.
- **AFTER_FLUSH** (`Oro\Bundle\FormBundle\Event\FormHandler\Events::AFTER_FLUSH`) - uses AfterFormProcessEvent,
triggered right after flushing of object manager.

If developer wants to use custom form handler then he should trigger these events in custom handler 
to support form processing consistency.

Update Handler Facade Service
------------------------------------------------------
Service: `@oro_form.update_handler`

Class: `Oro\Bundle\FormBundle\Model\UpdateHandlerFacade`

The default common way to handle forms in Oro packages is to use `Oro\Bundle\FormBundle\Model\UpdateHandlerFacade::update` 
method from the service with your custom arguments provided.

Among usual arguments there are two most valuable from the point of reuse. 
 
 The `handler` - for custom handler purpose:
- `string` - an alias of registered by tag `oro_form.form.handler` `Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface` implementation service
- `callable` - a callback to perform handling (see FormHandlerInterface::process() for arguments)
- `Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface` - an instance of handler itself
- `null` - to use `default` registered under tag `oro_form.form.handler` handler service that implements interface above

  The `dataProvider` - for custom template data purpose:
  
    @param FormTemplateDataProviderInterface|string|callable|null $resultProvider to provide template data
- `string` - an alias of registered by tag `oro_form.form_template_data_provider` service that implements `Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface`
- `callable` - callback to provide data (see FormTemplateDataProviderInterface::getData() for arguments)
- `Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface` - an instance of provider itself
- `null` - to use `default` registered provider (usually it returns `['form' => FormView $instance]`)
       
 Custom TemplateDataProvider and FormHandler can be reused in different parts of platform, such as 
 [WorkflowBundle Custom Form Configuration](../../../../WorkflowBundle/Resources/doc/reference/workflow/transition-forms.md#custom-form-type-example).
 
 **Example of custom handler and data provider services registration and usage:**

 
 Services:
 ```YML
 services:
    my_bundle.form.user_handler:
        class: MyBundle\Form\UserHandler
        tags:
            - { name: oro_form.form.handler, alias: user_handler }
    my_bundle.form.user_update_data_provider:
        class: MyBundle\Form\UserUpdateDataProvider
        tags:
            - { name: oro_form.form_template_data_provider, alias: user_update_data }
 ```
 A pseudo controller:
 ```PHP
 class UserController {
    public function createAction(Request $reqeust)
    {
        return $this->get('oro_form.update_handler')->update(
            new User(),
            $this->get('form_factory')->create(\MyBundle\Form\UserType::class),
            'Success! User created!',
            $request,
            'user_handler', //using an alias tagged my_bundle.form.user_handler service
            'user_update_data' //using an alias tagged my_bundle.form.user_update_data_provider service
        );
    }
 }
 ```
 
 The separating of responsibilities above (such as division of usual controller update process parts) is a powerful 
 feature that might help you to use all OroPlatform-based functionality easier.
  
