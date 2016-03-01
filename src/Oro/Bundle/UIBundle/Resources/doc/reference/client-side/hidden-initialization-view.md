<a name="HiddenInitializationView"></a>
## HiddenInitializationView ⇐ <code>BaseView</code>
**Extends:** <code>BaseView</code>  
**Kind**: global class  
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

