# OroFeatureToggleBundle

OroFeatureToggleBundle provides the ability for the application users to enable or disable application features using the system configuration UI, and assists developers in managing the application flow, depending on the status of this feature.

## How to define new feature

Features are defined with configuration files place in `Resources/config/oro/features.yml`.
Each feature consists of one required option - the label. Out of the box feature may be configured with next sections:
 - label - feature title
 - description - feature description
 - toggle - system configuration option key that will be used as feature toggle
 - dependencies - list of feature names that current feature depends on
 - routes - list of route names
 - configuration - list of system configuration groups and fields
 - workflows - list of workflow names
 - processes - list of process names
 - operations - list of operation names
 - api_resources - list of entity FQCNs
 - commands - list of commands which depend on the feature. Running these commands is impossible or is not reasonable when the feature is disabled
 
Example of features.yml configuration

```yml
features:
    acme:
        label: acme.feature.label
        description: acme.feature.description
        toggle: acme.feature_enabled
        dependencies:
            - foo
            - bar
        routes:
            - acme_entity_view
            - acme_entity_create
        configuration:
            - acme_general_section
            - acme.some_option
        workflows:
            - acme_sales_flow
        processes:
            - acme_some_process
        operations:
            - acme_some_operation
        api_resources:
            - Acme\Bundle\Entity\Page
        commands:
            - oro:search:index
```

## Adding new options to feature configuration

Feature configuration may be extended with new configuration options. To add new configuration option feature configuration
 that implements ConfigurationExtensionInterface should be added and registered with `oro_feature.config_extension` tag.
For example there are some Acme Processors which should be configured with `acme_processor` option

Configuration extension:
```php
<?php

namespace Acme\Bundle\ProcessorBundle\Config;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtensionInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class FeatureConfigurationExtension implements ConfigurationExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function extendConfigurationTree(NodeBuilder $node)
    {
        $node
            ->arrayNode('acme_processor')
                ->prototype('variable')
                ->end()
            ->end();
    }
}
```

Extension registration:
```yaml
services:
    acme.configuration.feature_configuration_extension:
        class: Acme\Bundle\ProcessorBundle\Config\FeatureConfigurationExtension
        tags:
            - { name: oro_feature.config_extension }
```

## Helper functionality to check feature state

Feature state is determined by `FeatureChecker`. There are proxy classes that expose feature check functionality to
layout updates, operations, workflows, processes and twig.

Feature state is resolved by `isFeatureEnabled($featureName, $scopeIdentifier = null)`
 
Feature resource types are nodes of feature configuration (routes, workflows, configuration, processes, operations, api_resources),
resources are their values. Resource is disabled if it is included into at least one disabled feature. 
Resource state is resolved by `public function isResourceEnabled($resource, $resourceType, $scopeIdentifier = null)` 

### Layout updates

 - Check feature state `=data['feature'].isFeatureEnabled('feature_name')`
 - Check resource state `=data['feature'].isResourceEnabled('acme_product_view', 'routes')`
 
 Set block visibility based on feature state:
```yaml
layout:
    actions:
        - '@add':
            id: products
            parentId: page_content
            blockType: datagrid
            options:
                grid_name: products-grid
                visible: '=data["feature"].isFeatureEnabled("product_feature")'
```

### Processes, workflows, operations

In Processes, workflows and operations config expression may be used to check feature state

 - Check feature state 

```yaml
'@feature_enabled': 
    feature: 'feature_name'
    scope_identifier: $.scopeIdentifier
```

 - Check resource state 

```yaml
'@feature_resource_enabled': 
    resource: 'some_route'
    resource_type: 'routes'
    scope_identifier: $.scopeId
```

### Twig

 - Check feature state `feature_enabled($featureName, $scopeIdentifier = null)`
 - Check resource state `feature_resource_enabled($resource, $resourceType, $scopeIdentifier = null)`

## Including a service into a feature

Service that need feature functionality needs to implement `FeatureToggleableInterface` interface.
All checks are done by developer.

OroFeatureToggleBundle provides helper functionality to inject feature checker and feature name 
into services marked with `oro_featuretogle.feature` tag. 
`FeatureCheckerHolderTrait` contains implementation of methods from `FeatureToggleableInterface`.

As example some form extension may extend external form and we want to include this extension 
functionality into a feature. In this case `FeatureChecker` should be injected into service
and feature availability should be checked where needed.


Extension:
```php
<?php

namespace Acme\Bundle\CategoryBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;

class ProductFormExtension extends AbstractTypeExtension implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;
    
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'acme_product';
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }
        
        $builder->add(
            'category',
            'acme_category_tree',
            [
                'required' => false,
                'mapped' => false,
                'label' => 'Category'
            ]
        );
    }
}
```

Extension registration:
```yaml
services:
    acme_category.form.extension.product_form:
        class: Acme\Bundle\CategoryBundle\Form\Extension\ProductFormExtension
    tags:
        - { name: oro_featuretogle.feature, feature: acme_feature }
```

## Feature state checking

Feature state is checked by feature voters. All voters are called each time you use the `isFeatureEnabled()` or `isResourceEnabled()` method on feature checker.
Feature checker makes the decision based on configured strategy defined in system configuration or per feature, which can be: affirmative, consensus or unanimous.

By default `ConfigVoter` is registered to check features availability.
It checks feature state based on value of toggle option, defined in features.yml configuration.
 
A custom voter needs to implement `Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface`.
Suppose we have State checker that return decision based on feature name and scope identifier.
If state is valid feature is enabled, for invalid state feature is disabled in all other cases do not vote.
Such voter will look like this:

```php
<?php

namespace Acme\Bundle\ProcessorBundle\Voter;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

class FeatureVoter implements VoterInterface
{
    /**
     * @var StateChecker
     */
    private $stateChecker;
    
    /**
     * @param StateChecker $stateChecker
     */
    public function __construct(StateChecker $stateChecker) {
        $this->stateChecker = $stateChecker;
    }
    
    /**
     * @param string $feature
     * @param object|int|null $scopeIdentifier
     * return int either FEATURE_ENABLED, FEATURE_ABSTAIN, or FEATURE_DISABLED
     */
    public function vote($feature, $scopeIdentifier = null)
    {
        if ($this->stateChecker($feature, $scopeIdentifier) === StateChecker::VALID_STATE) {
            return self::FEATURE_ENABLED;
        }
        if ($this->stateChecker($feature, $scopeIdentifier) === StateChecker::INVALID_STATE) {
            return self::FEATURE_DISABLED;
        }
        
        return self::FEATURE_ABSTAIN;
    }
}
```

Now voter should be configured:
```yml
services:
    acme_process.voter.feature_voter:
        class: Acme\Bundle\ProcessorBundle\Voter\FeatureVoter
        arguments: [ '@acme_process.voter.state_checker' ]
        tags:
            - { name: oro_featuretogle.voter }
```

## Changing the Decision Strategy
 
There are three strategies available:

 - *affirmative*
      
      This grants access as soon as there is one voter granting access;
 - *consensus*
 
    This grants access if there are more voters granting access than denying;
 - *unanimous* (default)
 
    This only grants access once all voters grant access.
    
Strategy configuration (may be defined in Resources/config/oro/app.yml)
```
oro_featuretoggle:
    strategy: affirmative
    allow_if_all_abstain: true
    allow_if_equal_granted_denied: false
```
or in feature definition
```yml
features:
    acme:
        label: acme.feature.label
        strategy: affirmative
        allow_if_all_abstain: true
        allow_if_equal_granted_denied: false
```


## Using checker for commands

Commands launched as subcommands cannot be skipped globally. To avoid running such commands, add an implementation of 
FeatureCheckerAwareInterface to your parent command, import FeatureCheckerHolderTrait (via `use FeatureCheckerHolderTrait;`), and check the 
feature status via featureChecker that is automatically injected into your command.

```php
<?php

namespace Acme\Bundle\FixtureBundle\Command;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;

class LoadDataFixturesCommand implements FeatureCheckerAwareInterface
{

    use FeatureCheckerHolderTrait;
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commands = [
            'oro:cron:analytic:calculate' => [],
            'oro:b2b:lifetime:recalculate'          => ['--force' => true]
        ];
    
        foreach ($commands as $commandName => $options) {
            if ($this->featureChecker->isResourceEnabled($commandName, 'commands')) {
                $command = $this->getApplication()->find($commandName);
                $input = new ArrayInput(array_merge(['command' => $commandName], $options));
                $command->run($input, $output);
            }
        }
    }
}
```
