#Configuration#

### Table of Contents ###

- [Getting Started](./getting-started.md)
- Configuration
- [Configuration merging](./configuration-merge.md)
- [Basic usage](./usage.md)

##Configuration tree##

		->info('Configuration of charts')
	    ->useAttributeAsKey('name')
	    ->prototype('array')
	        ->children()
				->scalarNode('label')
	                ->info('The label of chart')
	                ->cannotBeEmpty()
	                ->isRequired()
	            ->end()
	            ->arrayNode('data_schema')
	                ->info('Schema of chart data fields')
	                ->prototype('array')
	                    ->children()
	                        ->scalarNode('label')
	                            ->info('Name of chart data field')
	                            ->cannotBeEmpty()
	                            ->isRequired()
	                        ->end()
	                        ->scalarNode('name')
	                            ->info('Label of chart data field')
	                            ->cannotBeEmpty()
	                            ->isRequired()
	                        ->end()
	                        ->booleanNode('required')
	                            ->info('Is chart data field required')
	                            ->isRequired()
	                        ->end()
	                    ->end()
	                ->end()
	            ->end()
	            ->arrayNode('settings_schema')
	                ->info('Schema of chart settings fields')
	                ->prototype('array')
	                    ->children()
	                        ->scalarNode('name')
	                            ->info('Name of chart data field')
	                            ->cannotBeEmpty()
	                            ->isRequired()
	                        ->end()
	                        ->scalarNode('label')
	                            ->info('Name of chart settings field')
	                            ->cannotBeEmpty()
	                            ->isRequired()
	                        ->end()
	                        ->scalarNode('type')
	                            ->info('Form type of chart settings field')
	                            ->cannotBeEmpty()
	                            ->isRequired()
	                        ->end()
	                        ->arrayNode('options')
	                            ->info('Options of form type of chart settings field')
	                            ->prototype('variable')
	                            ->end()
	                        ->end()
	                    ->end()
	                ->end()
	            ->end()
	            ->arrayNode('default_settings')
	                ->info('Default settings of chart')
	                ->prototype('variable')
	                ->end()
	            ->end()
	            /** @todo Remove chart data transformer */
	            ->scalarNode('data_transformer')
	                ->info('Chart data transformer')
	                ->defaultValue(self::DEFAULT_DATA_TRANSFORMER_SERVICE)
	                ->cannotBeEmpty()
	            ->end()
	            ->scalarNode('template')
	                ->info('Template of chart')
	                ->cannotBeEmpty()
	                ->isRequired()
	            ->end()
**Description**


- Chart key used for identify chart type (line_chart in example below)

- Chart label used for text representation of chart type

- data_schema array of elements which used to form data array for chart view
	- name - name of parameter in data array
	- label - string representation used in chart configure
	- require - is parameter required

- settings_schema array of charts settings available for changing
	- name    - setting name
	- label   - string representation used in chart configure
	- type    - setting form type
	- options - form type additional options

- default_settings array of default chart settings

- template - chart view template

**Configuration Example**

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
		        template: OroChartBundle:Chart:line.html.twig