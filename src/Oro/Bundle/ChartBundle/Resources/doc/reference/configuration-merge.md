#Configuration merging#

### Table of Contents ###

- [Getting Started](./getting-started.md)
- [Configuration](./chart-configuration.md)
- Configuration merging
- [Basic usage](./usage.md)

##Description##

Bundle provides possibility to merge chart configs or override some of them. An example:

First bundle configuration:

	oro_chart:
	    line_chart:
	        label: oro.chart.line_chart.label
	        data_schema:
	          - name: label
	            label: oro.chart.line_chart.params.label
	            required: true
	          - name: value
	            label: oro.chart.line_chart.params.value
	            required: true
	        settings_schema:
	          - name: connect_dots_with_line
	            label: oro.chart.line_chart.settings.connect_dots_with_line
	            type: checkbox
	            options: { required: false }
	        default_settings:
	            chartColors: ['#ACD39C']
	        template: default:Chart:line.html.twig


Second bundle configuration

	oro_chart:
	    line_chart:
	        default_settings:
	            chartColors: 		 '7fab90,fdbc7c,73a4e1,bedb99,5a8980,ceeaf2,d6a970,fee090'
				chartHighlightColor: '#FF5E5E'
	        template: additional:Chart:line.html.twig


Result chart configuration

	oro_chart:
	    line_chart:
	        label: oro.chart.line_chart.label
	        data_schema:
	          - name: label
	            label: oro.chart.line_chart.params.label
	            required: true
	          - name: value
	            label: oro.chart.line_chart.params.value
	            required: true
	        settings_schema:
	          - name: connect_dots_with_line
	            label: oro.chart.line_chart.settings.connect_dots_with_line
	            type: checkbox
	            options: { required: false }
	        default_settings:
	            chartColors: 		 '7fab90,fdbc7c,73a4e1,bedb99,5a8980,ceeaf2,d6a970,fee090'
	            chartHighlightColor: '#FF5E5E'
	        template: additional:Chart:line.html.twig

