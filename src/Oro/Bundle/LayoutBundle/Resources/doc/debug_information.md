# Debug Information

## Symfony Profiler

In **Symfony Profiler** page you can find section **Layout** that contains:
 - **Context Items**
 - **Context Data Items**
 - **Layout Tree**

 ### Layout Tree

In left part of **Layout Tree** section you can see tree of **block views** with **block id** and **block type**.
In the right part of **Layout Tree** section you can find information of chosen **block view**, it contains options on **build block**, **build view** and **finish view** stages, also it contains all **block view** variables.

Also you can disable **Layout Tree** in developer toolbar, go to `System - Configuration - Development Settings - Generate Layout Tree Dump For The Developer Toolbar`

**IMPORTANT:** This options works with debug mode enabled only

## Developer toolbar

In the developer toolbar panel you can find icon that shows **count of block views** for current page. On mouse over it shows **layout context items** information.

![Layout developer toolbar](./images/developer_toolbar_panel.png "Layout developer toolbar")

## Debug layout blocks

You can enable **block debug information**, go to `System - Configuration - Development Settings - Include Block Debug Info Into HTML`

Each block in HTML has data attributes:

- `data-layout-debug-block-id` - unique identifier of current block
- `data-layout-debug-block-template` - template of current block that was rendered

**IMPORTANT:** If you want to render block debug information in HTML, you need to define `{{ block('block_attributes') }}` **for each twig block you have**.

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


