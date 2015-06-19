<a name="HiddenInitializationView"></a>
## HiddenInitializationView ⇐ <code>BaseView</code>
**Kind**: global class  
**Extends:** <code>BaseView</code>  

* [HiddenInitializationView](#HiddenInitializationView) ⇐ <code>BaseView</code>
  * [new HiddenInitializationView()](#new_HiddenInitializationView_new)
  * [.autoRender](#HiddenInitializationView#autoRender)

<a name="new_HiddenInitializationView_new"></a>
### new HiddenInitializationView()
View allows hide part of DOM tree till all page components will be initialized

Usage sample:

> Please note that all div's attributes are required for valid work.

```html
<div class="invisible"
        data-page-component-module="oroui/js/app/components/view-component"
        data-page-component-options="{'view': 'oroui/js/app/views/hidden-initialization-view'}"
        data-layout="separate">
    <!-- write anything here -->
</div>
```

<a name="HiddenInitializationView#autoRender"></a>
### hiddenInitializationView.autoRender
**Kind**: instance class of <code>[HiddenInitializationView](#HiddenInitializationView)</code>  
