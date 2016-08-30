OroFeatureToggleBundle
=========================

This bundle provide ability to manage system features. Feature is as a set of grouped functionality.

How to define new feature
-------------------------

Features are defined with configuration files place in Resources/oro/features.yml.
Each feature consists of required options: title and toggle. Out of the box feature may be configured with next sections:
 - title - feature title
 - description - feature description
 - toggle - system configuration option key that will be used as feature toggle
 - dependency - list of feature names that current feature depends on
 - route - list of route names
 - configuration - list of system configuration groups and fields
 - workflow - list of workflow names
 - process - list of process names
 - operation - list of operation names
 - api - list of entity FQCNs
 
Example of features.yml configuration

```yml
features:
    acme:
        title: acme.feature.label
        description: acme.feature.description
        toggle: acme.feature_enabled
        dependency:
            - foo
            - bar
        route:
            - acme_entity_view
            - acme_entity_create
        configuration:
            - acme_general_section
            - acme.some_option
        workflow:
            - acme_sales_flow
        process:
            - acme_some_process
        operation:
            - acme_some_operation
        api:
            - Acme\Bundle\Entity\Page
```

Adding new section to features configuration
--------------------------------------------

Feature configuration may be extended with new configuration nodes. To add new configuration node feature configuration
 that implements ConfigurationExtensionInterface should be added and registered with `oro_feature.config_extension` tag.
For example there are some Acme Processors which should be configured with `acme_processor` key

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

Including a service into a feature
---------------------------------

Sometimes there is a need to add some service to feature. As example some form extension may extend external form 
and we want to include this extension functionality to feature. In this case `FeatureChecker` should be injected into service
and feature availability should be checked where needed.
OroFeatureToggleBundle provides helper functionality to inject feature checker and feature name to your service.
First of all service should implement `FeatureToggleableInterface` interface which methods are implemented in `FeatureCheckerHolderTrait`
To mark that service is related to some feature it should be marked with `oro_featuretoggle.checker.feature_checker` and `feature` name.

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
        { name: oro_featuretogle.feature, feature: acme_feature }
```

Feature state checking
----------------------

Feature state is checked by feature voters. All voters are called each time you use the `isFeatureEnabled()` or `isResourceEnabled()` method on feature checker.
Feature checker makes the decision based on configured strategy defined in system configuration, which can be: affirmative, consensus or unanimous.

By default `ConfigVoter` is registered to check features availability.
It checks feature state based on value of toggle option, defined in features.yml configuration.
 
A custom voter needs to implement `VoterInterface`.
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
     * /
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

Changing the Decision Strategy
------------------------------
 
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
