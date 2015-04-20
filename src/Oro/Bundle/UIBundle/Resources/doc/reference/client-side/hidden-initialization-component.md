<a name="HiddenInitializationComponent"></a>
## HiddenInitializationComponent ⇐ <code>BaseComponent</code>
**Extends:** <code>BaseComponent</code>  
**Kind**: global class  

* [HiddenInitializationComponent](#HiddenInitializationComponent) ⇐ <code>BaseComponent</code>
  * [new HiddenInitializationComponent()](#new_HiddenInitializationComponent_new)
  * [.initialize](#HiddenInitializationComponent#initialize)

<a name="new_HiddenInitializationComponent_new"></a>
### new HiddenInitializationComponent()
Component allows hide part of DOM tree till all page components will be initialized

Usage sample:

> Please note that all div's attributes are required for valid work.

```html
<div class="invisible"
        data-page-component-module="oroui/js/app/components/hidden-initialization-component"
        data-layout="separate">
    <!-- write anything here -->
</div>
```

<a name="HiddenInitializationComponent#initialize"></a>
### hiddenInitializationComponent.initialize
**Kind**: instance class of <code>[HiddenInitializationComponent](#HiddenInitializationComponent)</code>  
