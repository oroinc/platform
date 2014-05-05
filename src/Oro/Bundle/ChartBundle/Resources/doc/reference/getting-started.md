# Getting Started

### Table of Contents

- Getting Started
- [Configuration](./chart-configuration.md)
- [Basic usage](./usage.md)

## What is chart bundle ##
Chart bundle provide functionality for display different types of chart.
It is solution that allows user to view data in useful chart format.
Bundle support such chart types as:

- line chart
- pie chart
- flow chart

## Main classes ##

**Oro\Bundle\ChartBundle\Model\Data\DataInterface**
- Interface that can be passed to chart builder as source data.

**Oro\Bundle\ChartBundle\Model\ChartView**
- View representation that can be used to render chart.

**Oro\Bundle\ChartBundle\Model\ChartViewBuilder**
- Builder can be used to create view instance.

**Oro\Bundle\ChartBundle\Model\ConfigProvider**
- Provide access to oro_chart configuration.

## How it works ##

Developer use chart view builder to create instance of chart view that can be used to render chart in template.
[See more](./usage.md)
