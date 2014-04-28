#Configuration merge#

### Table of Contents ###

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
	            chartColors: ['#ACD39C', '#BE9DE2', '#6598DA', '#ECC87E', '#A4A2F6', '#6487BF', '#65BC87', '#8985C2', '#ECB574', '#84A377']
	            chartFontSize: 9
	            chartFontColor: '#454545'
	            chartHighlightColor: '#FF5E5E'
	        template: default:Chart:line.html.twig


Second bundle configuration

	oro_chart:
	    line_chart:
	        default_settings:
	            chartColors: '7fab90,fdbc7c,73a4e1,bedb99,5a8980,ceeaf2,d6a970,fee090'
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
	            chartColors: '7fab90,fdbc7c,73a4e1,bedb99,5a8980,ceeaf2,d6a970,fee090'
	            chartFontSize: 9
	            chartFontColor: '#454545'
	            chartHighlightColor: '#FF5E5E'
	        template: additional:Chart:line.html.twig

