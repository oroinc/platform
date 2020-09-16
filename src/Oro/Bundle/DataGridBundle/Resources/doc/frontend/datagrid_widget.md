# Datagrid widget

Datagrid widget provide ability to render datagrid by name as widget.
When datagrid is rendered inside widget it's rowClickAction will be disabled and replaced
with dummy action. This action will trigger `grid-row-select` event on widget instance with
data parameter of next structure:

``` javascript
{
    datagrid: datagridInstance,
    model: selectedModel
}
```

Usage example:

``` html
{% import 'OroUIBundle::macros.html.twig' as UI %}

<div>
    {{ oro_widget_render({
        'widgetType': 'block',
        'url': path('oro_datagrid_widget', {gridName: 'groups-grid'}),
        'title': 'User Groups'|trans,
        'alias': 'user-groups-widget'
    }) }}
    <div {{ UI.renderPageComponentAttributes({
        'module': 'your/row-selection/handler',
        'options': {
            'alias': 'user-groups-widget'
        }
    })></div>
</div>

```
Create js module with the handler definition `'your/row-selection/handler'` as shown in example below, don't forget to add this module to the list of `dynamic-imports` in `jsmodules.yml`  

``` javascript
import widgetManager from 'oroui/js/widget-manager';

export default function(options) {
    widgetManager.getWidgetInstanceByAlias(options.alias, function(widget) {
        widget.on('grid-row-select', function(data) {
            console.log(data.datagrid);        // datagrid instance
            console.log(data.model);           // row data object
            console.log(data.model.get('id')); // row attribute
        });
    });
};
```
