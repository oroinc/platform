Scroll Data Customization
=========================

### General Information

To customize data rendered with macros `scrollData(...)` from `macros.html.twig` developer can use special event
triggered in the very beginning of marcos processing. Event name has format `oro_ui.scroll_data.before.<dataTarget>`
where `<dataTarget>` is a string identifier passed to macros as a first argument. This way developer can modify
data that will be rendered on any page where scrollData macros is used.

Event object used in this case is `Oro\Bundle\UIBundle\Event\BeforeListRenderEvent` - it provides `\Twig_Environment`
object, data wrapped into `Oro\Bundle\UIBundle\View\ScrollData` object and optional `FormView` instance. Data can be 
completely replaced.


### Scroll Data

Scroll data object provides several useful methods to add information to scroll data

- **addBlock($title, $priority, $class, $useSubBlockDivider)** - adds new block in the end of the list, 
block title is required 
- **addSubBlock($blockId, $title)** - adds new subblock to block with specified identifier (array key) 
- **addSubBlockData($blockId, $subBlockID, $html)** - adds HTML code to the existing subblock inside specified block 


### Customization Example

Lets look at an example of scroll data customization. Here is definition of listener used to customize scroll data:

```yml
user_update_scroll_data_listener:
    class: ...
    tags:
        - { name: kernel.event_listener, event: oro_ui.scroll_data.before.user-profile, method: onUserUpdate }
```

This definition shows service used as an event listener for event `oro_ui.scroll_data.before.user-profile` with handler
method `onUserUpdate`. This listener will be executed before rendering of user update page 
(it has identifier _user-profile_). Here is how such listener can look like:

```php
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class UpdateUserListener
{
    /**
     * @param BeforeListRenderEvent $event
     */
    public function onUserUpdate(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'MyBundle:User:my_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }
}

```

And the correspondent template `MyBundle:User:my_update.html.twig`:

```
{{ form_row(form.myField) }}
```

This example demonstrates adding of additional field to User update page - template is rendered via environment object
and added to scroll data.
