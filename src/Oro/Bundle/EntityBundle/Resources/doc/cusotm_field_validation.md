
Using `oro_entity.manager.entity_field_validator` service you can add custom field validation that can be placed 
in your bundle.

Example:

    # Validator
    oro_acme.validator.acme_custom_grid_field_validator:
        class: %oro_acme.validator.acme_custom_grid_field_validator.class%
        tags:
            - {name: oro_entity.custom_grid_field_validator, entity_name: Oro_Bundle_AcmeBundle_Entity_Foo }

Each validator should implement `Oro\Bundle\EntityBundle\Entity\Manager\Field\CustomGridFieldValidatorInterface` and 
add tag description. 

Tag should contain `name` and `entity_name`:
    * `entity_name` - should contain entity name which will be performed. You should use 
    `str_replace('\\', '_', ClassUtils::getClass($entity))` transformation ob object to get `entity_name` which 
    could be written into service tag block
