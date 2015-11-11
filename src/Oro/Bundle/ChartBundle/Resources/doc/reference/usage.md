# Basic usage

### Table of Contents

- [Getting Started](./getting-started.md)
- [Configuration](./chart-configuration.md)
- Basic usage

##Controller example##

```php

	public function exampleAction(){
		// items - array in format: array(array("id" => 1, "firsName" => 'Alex', "fee" => 42), ...)
		$items = $this->getChartData();

    	$viewBuilder = $this->container->get('oro_chart.view_builder');

    	$view = $viewBuilder
    	    ->setOptions(array('name' => 'line_chart'))
    	    ->setArrayData($items)
    	    ->setDataMapping(array('label' => 'firstName', 'value' => 'fee'))
            ->getView();

		return $this->render('ExampleBundle:Example:example.html.twig', array('chartView' => $view));
	}

```

Configurations list you can see ([here](./chart-configuration.md))

##View Example##

```
    {{ chartView.render()|raw }}
```
