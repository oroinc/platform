## Add new configuration scope ##

To add new config scope, developer should do next steps.

### Add scope manager ###

A scope manager is a class provides access to configuration attributes is a particular scope. This class should extend [AbstractScopeManager](./../../Config/AbstractScopeManager.php).

In the simplest case, scope manager will looks like this:

``` php
namespace Acme\Bundle\SomeBundle\Config;

use Oro\Bundle\ConfigBundle\Config\AbstractScopeManager;

/**
 * Test config scope
 */
class TestScopeManager extends AbstractScopeManager
{
    /**
     * {@inheritdoc}
     */
    public function getScopedEntityName()
    {
        return 'test'; //scope entity name
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeId()
    {
        return 0; // scope entity id (can be different for different cases)
    }
}
```

This manager should be registered as the service with tag `oro_config.scope` :

```yml

    acme_test.scope.test:
        class: Acme\Bundle\SomeBundle\Config\TestScopeManager
        public: false
        parent: oro_config.scope_manager.abstract
        tags:
            - { name: oro_config.scope, scope: test, priority: 50 }

```

After this, the scope `test` will be used during retrieving some config value. This scope will be between `global` and `user` scopes.
A developer can use this scope with `oro_config.test` config provider.

### Change scope values via UI ###

To be able to change values for new scope, developer should add new tree structure for this scope in `system_configuration.yml` file, e.g.:

```yml

    oro_system_configuration:
       tree:
          test_configuration:
              platform:
                  children:
                      general_setup:
                          children:
                              localization:
                                  priority: 255
                                  children:
                                      locale_settings:
                                          priority: 100
                                          children:
                                              - oro_locale.locale
    ...                                          

```

In this example, user will be allow to change `locale` settings on `test` scope.

After this, developer should add new form provider for test scope:

```php
    
    <?php
   
    namespace Acme\Bundle\SomeBundle\Provider;
    
    use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;
    
    class TestConfigurationFormProvider extends SystemConfigurationFormProvider
    {
        const TEST_TREE_NAME  = 'test_configuration';
    
        /**
         * {@inheritdoc}
         */
        public function getTree()
        {
            return $this->getTreeData(self::TEST_TREE_NAME, self::CORRECT_FIELDS_NESTING_LEVEL);
        }
    }

```

register it as a service with `oro_config.configuration_provider` tag:

```yml

      acme_test.provider.form_provider.test:
          class: %acme_test.provider.form_provider.test.class%
          arguments:
              - []
              - @form.factory
              - @oro_security.security_facade
          tags:
              -  { name: oro_config.configuration_provider }
          lazy: true
```

add new action to manipulate data:

```php

    /**
     * @Route(
     *      "/test-config-route/{activeGroup}/{activeSubGroup}",
     *      name="test_config",
     *      requirements={"id"="\d+"},
     *      defaults={"activeGroup" = null, "activeSubGroup" = null}
     * )
     * @Template()
     */
    public function testConfigAction($activeGroup = null, $activeSubGroup = null)
    {
        $provider = $this->get('acme_test.provider.form_provider.test');

        list($activeGroup, $activeSubGroup) = $provider->chooseActiveGroups($activeGroup, $activeSubGroup);

        $tree = $provider->getTree();
        $form = false;

        if ($activeSubGroup !== null) {
            $form = $provider->getForm($activeSubGroup);

            $manager = $this->get('oro_config.test');

            if ($this->get('oro_config.form.handler.config')
                ->setConfigManager($manager)
                ->process($form, $this->getRequest())
            ) {
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.config.controller.config.saved.message')
                );

                // outdate content tags, it's only special case for generation that are not covered by NavigationBundle
                $taggableData = ['name' => 'organization_configuration', 'params' => [$activeGroup, $activeSubGroup]];
                $sender       = $this->get('oro_navigation.content.topic_sender');

                $sender->send($sender->getGenerator()->generate($taggableData));
            }
        }

        return array(
            'data'           => $tree,
            'form'           => $form ? $form->createView() : null,
            'activeGroup'    => $activeGroup,
            'activeSubGroup' => $activeSubGroup,
        );
    }
```

and the template:
 
```
    {% extends 'OroConfigBundle::configPage.html.twig' %}
    {% import 'OroUIBundle::macros.html.twig' as UI %}
        
    {% set pageTitle = [
            'acme_test.some_label'|trans
        ]
    %}
    
    {% set formAction    = path(
            'test_config',
            {activeGroup: activeGroup, activeSubGroup: activeSubGroup}
        )
    %}
    {% set routeName = 'test_config' %}

```
