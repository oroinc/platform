Highlight Text View
===================

Highlight Text View is used for highlight some text in view element
Example: highlight query string in search results.

Initialization
--------------
Initialize in twig:
```twig
    //example from System Configuration page content
    <div data-page-component-view="{{ {
        view: 'oroui/js/app/views/highlight-text-view',
        highlightSwitcherContainer: 'div.system-configuration-content-header',
        highlightStateStorageKey: 'show-all-configuration-items-on-search',
        highlightSelectors: [
            'div.system-configuration-content-title',
            'h5.user-fieldset span',
            'div.control-label label',
            'i.tooltip-icon'
        ],
        toggleSelectors: {
            'div.control-group': 'div.control-group-wrapper',
            'fieldset.form-horizontal': 'div.system-configuration-content-inner'
        },
        viewGroup: 'configuration'
    }|json_encode }}">
        {{ _self.renderTabContent(data.form, data.content) }}
    </div>
```

Initialize in JavaScript:
```javascript
    //example from "oroui/js/app/views/jstree/base-tree-view"
    this.subview('highlight', new HighlightTextView({
        el: this.el,
        viewGroup: 'configuration',
        highlightSelectors: ['.jstree-search']
    }));
```

Options
-------

- `text:string` - text to highlight
- `viewGroup:string` - used as mediator event prefix
- `highlightSwitcherContainer:string` 'class or attribute in which will render template of highlight switcher',
- `highlightStateStorageKey:string` 'localStorage key which will contain state of visibility for not found/highlighted elements',
- `highlightClass:string` - class used for text highlight
- `notFoundClass:string` - class used for mark content without highlighted elements 
- `foundClass:string` - class used for mark content with highlighted elements 
- `highlightSelectors:array` - array of selectors that used to find elements to highlight
- `toggleSelectors:object` - array of selectors that used to find elements to mark as found or not found
