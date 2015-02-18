# Layout updates

## Configuration

Configuration will be loaded from `Resources/config/oro/layout.yml`  files. For now there will be only one node `themes`, but in future it may contains another nodes as well.

### Themes configuration reference

| Option | Description | Required |
|------- |-------------|----------|
| `label` | Label will be displayed in theme management UI. | yes |
| `logo` | Logo that will be displayed in the UI. | no |
| `screenshot` | Screenshot for preview. This will be displayed in theme management UI. | no |
| `directory` | Directory name where to do look up for layout updates. By default equals to theme identifier | no |
| `parent` | Parent theme identifier. By default all theme is descendant of `base` theme | no |

**Example:**

```yml
# src/Acme/Bundle/DemoBundle/Resources/config/oro/layout.yml

oro_layout:
    themes:
        oro-gold:
            label:          Nice ORO gold theme
            directory:      OroGold
            parent:         oro-default
```
Where `oro-gold` and `oro-default` are themes unique identifiers. `parent` option may contains `~(null)` in case when developer do not want to inherit `base` theme.

## Directory structure

Each bundle could provide any number of layout updates for specific theme or for the `base` theme.
 
**Example:**

```bash
src/
    Acme/
        Bundle/
            AcmeDemoBundle/
                Resources/
                    layouts/
                        base/
                            update1.yml
                            update2.yml
                            ...
                        oro-gold/
                            update1.yml
                            update2.yml
                            ...
```
Also there is possibility to introduce new updates in `app/Resources/layouts/` folder and override existing one there as well (for example updates from `base` theme needs to be modified)

**Example:**

```bash
app/
    Resources
        layouts/
            new-theme/
                update1.yml
                update2.yml
        views/
        ...
        AcmeDemoBundle/
            layouts/
                base/
                    update1.yml # override of existing update in AcmeDemoBundle
                    ...
        ...
```
