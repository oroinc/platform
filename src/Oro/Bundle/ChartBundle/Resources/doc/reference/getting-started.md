# Getting Started

### Table of Contents

- Getting Started
- [Configuration](./chart-configuration.md)
- [Configuration merging](./configuration-merge.md)
- [Basic usage](./usage.md)

## What is Entity Merge ##
Chart bundle provide functionality for display different types of chart.
It is solution that allows user to view data in useful chart format.
Bundle support such chart types as:

- line chart
- pie chart
- flow chart



## Main Entities ##

Chart bundle consists of several related entities.

- **ArrayData**    		- realization of data interface for array.
- **DataGridData** 		- realization of data interface for data grid.
- **MappedData** 		- realization of data interface for mapping data to chart schema.
- **ChartView** 		- chart view representation.
- **ChartViewBuilder** 	- class responsible for chart view creation.
- **ConfigProvider** 	- provide access to oro_chart config node.


## How it works ##

Developer use ChartViewBuilder for build view, then call from view render method in twig template.
[See more](./reference/usage.md)