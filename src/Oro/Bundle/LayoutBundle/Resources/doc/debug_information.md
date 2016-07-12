# Debug Information

## Developer Toolbar

In developer toolbar you can find section **Layout** that contains:
 - **Layout Tree**
 - **Context Items**

Also you can disable **Layout Tree** in developer toolbar, go to `System - Configuration - Development Settings - Generate Layout Tree Dump For The Developer Toolbar`

## Debug layout blocks

You can enable **block debug information**, go to `System - Configuration - Development Settings - Include Block Debug Info HTML`

Each block in HTML has data attributes:

- `data-layout-debug-block-id` - unique identifier of current block
- `data-layout-debug-block-template` - template of current block that was rendered

**!!! Important !!!** If you want to render block debug information in HTML, you need to define `{{ block('block_attributes') }}` **for each twig block you have**.

**Example:**

```yaml
    ...
    {% block _page_container_widget %}
        <div{{ block('block_attributes') }}>
            {{ block_widget(block) }}
        </div>
    {% endblock %}

    {% block _header_widget %}
        <header{{ block('block_attributes') }}>
            {{ block_widget(block) }}
        </header>
    {% endblock %}
    ...
```


