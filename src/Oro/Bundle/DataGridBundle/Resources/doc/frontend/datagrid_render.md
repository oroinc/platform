#Datagrid render

Datagrid provide twig macros for datagrid render.

Usage example:

``` html
    {% import 'OroDataGridBundle::macros.html.twig' as dataGrid %}
    {{ dataGrid.renderGrid(name, params, renderParams) }}
```

`renderParams` provide ability to configure grid view.
Usage example:

``` html
    <script type="text/template" id="row-template-selector">
        <b><%= model.label %></b><br/>
        <%= model.description %>
    </script>

    {% set renderParams = {
        themeOptions: {
            tagName: 'div', #change grid table tags to div
            headerHide: true, #hide grid elements, allowed prefixes: header, footer
            bodyClassName: 'grid-my-body', #change element class name, allowed prefixes: header, headerRow, body, row, footer
            rowTemplateSelector: '#row-template-selector' #disable standard row renderer by cells and use given template for full row
        }
    } %}
```
