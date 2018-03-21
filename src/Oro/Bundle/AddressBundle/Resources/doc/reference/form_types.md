## Address Form Types

OroAddressBundle provides form types to render address entities by forms.

### Form Types Description

* **oro\_address** - encapsulates form fields for the Address entity;
* **oro\_address\_collection** - collection of form types for address entities;
* **oro\_country** - encapsulates form fields for the Country entity;
* **oro\_region** - encapsulates form fields for the Region entity.

### Classes Description

* **Form \ Type \ AddressType** - a base form for Address that includes form fields for the address attributes;
* **Form \ Type \ TypedAddressType** - extends AddressType, adds functionality to work with address types;
* **Form \ Type \ AddressType** - implementation of AbstractAddressType. The form type is "oro_address";
* **Form \ Type\ AddressCollectionType** - provides functionality to work with address collections. The form type is "oro_address_collection";
* **Form \ Type \ CountryType** - provides form types for the Country entity and is represented by the "oro_country" form type;
* **Form \ Type \ RegionType** - provides form types for the Region entity. The form type is "oro_region";
* **Form \ EventListener \ AddressCountryAndRegionSubscriber** - is responsible for processing relations between countries and regions by address forms;
* **Form \ EventListener \ FixAddressesPrimarySubscriber** - ensures that only the newly created/updated address is specified as primary for the selected owner. If also removes the primary status for the other addresses added previously; 
* **Form \ EventListener \ FixAddressesTypesSubscriber** - ensures that only the newly created/updated address type (shipping/billing) is specified as primary for the selected owner. If also removes the primary status for the other address types added previously;
* **Form \ Handler \ AddressHandler** - processes save for Address entity using specified form.
