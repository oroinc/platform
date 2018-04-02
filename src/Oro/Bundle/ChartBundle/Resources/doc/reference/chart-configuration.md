# Configuration

### Table of Contents

- [Getting Started](./getting-started.md)
- Configuration
- [Basic usage](./usage.md)

**Configuration Example**

```yml
charts:
  line_chart:                                       # Chart key used for identify chart type (line_chart in example below)
    label: oro.chart.line_chart.label               # Chart label used for text representation of chart type

    data_schema:                                    # Describe fields in data array
      - name: label                                 # Name of field in data array
        label: oro.chart.line_chart.params.label    # Label of field to use in chart form
        required: true                              # Is this field required
        field_name: field_name                      # Predefined field name for non-abstract charts, optional
      - name: value
        label: oro.chart.line_chart.params.value
        required: true
        field_name: field_name

    settings_schema:                                # Describe field of chart settings form
      - name: connect_dots_with_line                # Field name
        label: Connect dots with line               # Label of field in form
        type: checkbox                              # Form type of field
        options: { required: false }                # Options of field form type

    default_settings:
      chartColors: ["#ACD39C", "#BE9DE2", "#6598DA", "#ECC87E", "#A4A2F6", "#6487BF", "#65BC87", "#8985C2", "#ECB574", "#84A377"]
      chartFontSize: 9
      chartFontColor: "#454545"
      chartHighlightColor: "#FF5E5E"

    data_transformer: oro_chart.data_transformer.example # Custom data transformer

    template: OroChartBundle:Chart:line.html.twig
```
