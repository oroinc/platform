# Basic usage

### Table of Contents

- [Getting Started](./getting-started.md)
- [Configuration](./chart-configuration.md)
- [Configuration merging](./configuration-merge.md)
- Basic usage

##Controller example##
	public function exampleAction(){
		// items - array in format: array(array('id'=>1, 'firsName'=>'Alex', 'fee'=>42), ...)
		$items = $this->getChartData();

    	$viewBuilder = $this->container->get('oro_chart.view_builder')

    	$viewBuilder->setOptions(array('name' => 'line_chart'));

    	$viewBuilder->setArrayData($items);
    	$viewBuilder->setDataMapping(array('label' => 'firstName', 'value' => 'fee'));
		
		$view = $viewBuilder->getView();
    	
		return $this->render('ExampleBundle:Example:example.html.twig', array('chartView' => $view));
	}
    
Configurations list you can see ([here](./chart-configuration.md))

##View Example##

    {{ chartView.render()|raw }}