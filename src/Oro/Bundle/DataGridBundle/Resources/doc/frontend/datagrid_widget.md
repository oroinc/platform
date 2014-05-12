#Datagrid widget

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
<div>
    {{ oro_widget_render({
        'widgetType': 'block',
        'url': path('oro_datagrid_widget', {gridName: 'groups-grid'}),
        'title': 'User Groups'|trans,
        'alias': 'user-groups-widget'
    }) }}

    <script type="text/javascript">
        require(['oroui/js/widget-manager'],
        function(widgetManager) {
            widgetManager.getWidgetInstanceByAlias('user-groups-widget', function(widget) {
                widget.on('grid-row-select', function(data) {
                    console.log(data.datagrid);        // datagrid instance
                    console.log(data.model);           // row data object
                    console.log(data.model.get('id')); // row attribute
                });
            });
        });
    </script>
</div>
```
