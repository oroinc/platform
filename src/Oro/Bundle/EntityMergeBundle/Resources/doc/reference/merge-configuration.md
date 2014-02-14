# Configuration  #

---------

### Table of Contents ###

- [Getting Started](./getting-started.md)
	- [What is Entity Merge](./getting-started.md#what-is-entity-merge "What is Entity Merge")
	- [Main Entities](./getting-started.md#main-entities)
	- [How it works](./getting-started.md#how-it-works)
- [Classes Diagram](./classes-diagram.md)
- Configuration
	- [Entity configuration](#entity-configuration)
	- [Mass action configuration](#mass-action-configuration)
	- [Other configurations](#other-configurations)
	
---------

## Entity configuration ##

Entity can be configure on entity level and on fields level

Entity level configuration

         # Options for rendering entity as string in the UI.
         # If these options are empty __toString will be used (if it's available).
         #
         # Method of entity to cast object to string
         cast_method: ~
         # Twig template to render object as string
         template: ~
         # Enable merge for this entity
         enable:
            options:
                is_bool: true
 

Example:

     * @Config(
     *  ....
     *  defaultValues={
     *  ...
     *  "merge"={
     *  "enable"=true,
     *  "max_entities_count"=5
     *  }
     *  }
     * )

Fields Level configuration

    # Label of field that should be displayed for this field in merge UI, value will be translated
                label: ~
                # Enable merge for this field
                enable:
                    options:
                        is_bool: true
                # Hide field in merge
                hidden:
                    options:
                        is_bool: true
                # Make field read-only in merge
                readonly:
                    options:
                        is_bool: true
                # Mode of merge supports next values, value can be an array or single mode:
                #   replace - replace value with selected one
                #   merge   - merge all values into one (applicable for collections and lists)
                merge_modes:
                    options:
                        serializable: true
                # Flag for collection fields. This fields will support merge mode by default
                is_collection:
                    options:
                        is_bool: true
                # Options for rendering field value in the UI
                #
                # Method will be used to cast value to string (applicable only for values that are objects)
                cast_method: ~
                # Template can be used to render value of field
                template: ~
                # Method for setting value to entity
                setter: ~
                # Method for getting value to entity
                getter: ~
                # Can be used if you want to be able to merge this field for entity on other side of relation,
                # For example there is a Call entity with field referenced to Account using ManyToOne unidirectional relation.
                # As Account doesn't have access to collection of calls the only possible place to configure calls merging
                # for account is this field in Call entity
                relation_enable:
                    options:
                        is_bool: true
                # Same as merge_mode but used for relation entity
                relation_modes: ~
                # Same as label but used for relation entity
                relation_label: ~

Example:

    class Account
    {
     ...

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @ConfigField(defaultValues={"merge"={"enable"=true}})
     */
    protected $name;


## Mass action configuration ##

In merge mass action you can define:

1. label - which will be displayed in page with grid
2. icon - which will be displayed in page with grid
3. data_identifier - entity identifier
4. route - route to mass action
5. frontend_type - frontend part of mass action, which contain validation, etc
6. frontend_handle - mas action handler
7. route_parameters - additional route parameters for custom routing
8. entity_name - name of entity for merge (required)

Example:

    	   mass_actions:
			    	merge:
			    	type: merge
			    	entity_name: %orocrm_account.account.entity.class%
			    	data_identifier: a.id
			    	label: merge
			    	icon: edit


## Other configurations ##

You can define your own "Strategy", "Steps", "Accessor" in DI by using tags with names "oro_entity_merge.strategy", "oro_entity_merge.step", "oro_entity_merge.accessor".

Example:

    oro_entity_merge.strategy.replace:
        class: %oro_entity_merge.strategy.replace.class%
        arguments:
            - @oro_entity_merge.accessor.delegate
        tags:
            - { name: oro_entity_merge.strategy }
    


	oro_entity_merge.step.validate:
        class: %oro_entity_merge.step.validate.class%
        arguments:
            - @validator
        tags:
            - { name: oro_entity_merge.step }

	oro_entity_merge.accessor.inverse_association:
        class: %oro_entity_merge.accessor.inverse_association.class%
        arguments:
            - @oro_entity_merge.doctrine_helper
        tags:
            - { name: oro_entity_merge.accessor }