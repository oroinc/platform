## Address Validators

OroAddressBundle has specific validators that can be used to validate addresses and address collection.

### Classes Description

* **Validator \ Contraints \ ContainsPrimaryValidator** - checks an address collection to ensure that it contains only one primary address;
* **Validator \ Contraints \ ContainsPrimary** - contains an error message for ContainsPrimaryValidator;
* **Validator \ Contraints \ UniqueAddressTypesValidator** - checks an address collection to ensure that it has no more than one address per each address type;
* **Validator \ Contraints \ UniqueAddressTypes** - contains an error message for UniqueAddressTypesValidator.

### Example Of Usage

Validation configuration should be placed in the Resources/config/validation.yml file in the appropriate bundle.

```
Oro\Bundle\ContactBundle\Entity\Contact:
    properties:
        addresses:
            - Oro\Bundle\AddressBundle\Validator\Constraints\UniqueAddressTypes: ~
```
