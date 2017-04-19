# Mass action configuration

In the bundle `layout.yml`, using the `themeOptions` parameters, a theme developer may customize
and tune the way individual mass actions and the mass actions group show in the UI,
when the items delivered by the bundle are shown in the grid view.

Sample configuration in the `layout.yml` file(s) in the
`Resources/views/layouts/theme/page/folder` in the bundle (e.g. OrderBundle):

```yml
layout:
    actions:
        - '@setOption':
            id: test_datagrid_id
            optionName: grid_render_parameters
            optionValue:
                themeOptions:
                    cellActionsHideCount: 3
                    cellLauncherOptions:
                        launcherMode: 'icon-only' # 'icon-only' | 'icon-text' | 'text-only'
                        actionsState:  'hide'     # 'hide' | 'show'
```

## Controlling the actions list view

The `cellActionsHideCount` and `cellLauncherOptions > actionsState` parameters control the way mass actions collapse
into the show more group (`...`) and will be display on hover over the `...`.

When not collapsed, the actions show inline with the item: 'three dots' menu is hidden.

To collapse all actions into the `show more` group (`...`), set actionsState to `hide`.
In this case, the actionsHideCount value is ignored.
> You get similar outcome with the options `actionsState: show` and `actionsHideCount: 0`

User see only 'three dots' menu.

To keep all actions expanded, set `actionsState` to `show`
and set `actionsHideCount` to the reasonably large value (up to the max number of the actions you expect to get).

User see all line items.

To optimize the space organization, keep most used actions expanded and hide the less frequent ones.
To do so, set actionsHideCount to the average number of the frequently used actions (e.g. 3).

> some line items are **inline**, other are **hidden**.

User see only some line items and 'three dots' menu.

## Controlling the action view

Based on the `launcherMode` value, the individual mass actions may display in one of the following modes.

### Label and icon:

launcherMode: `icon-text`

```html
    <a class="action" href="#action_url">
        <i class="fa-<%= icon %>"></i>
        <%= label %>
    </a>
```

### Icon only:

launcherMode: `icon-only`

```html
    <a class="action" href="#action_url">
        <i class="fa-<%= icon %>"></i>
    </a>
```

### Label only:

launcherMode: `text-only`

```html
    <a class="action" href="#action_url">
        <%= label %>
    </a>
```
