## Address Entities

OroAddressBundle provides several entities to work with addresses.

### Classes Description

* **AbstractAddress** - encapsulates basic address attributes (label, street, city, country, first and last name etc.);
* **AbstractTypedAddress** - extends AbstractAddress and adds the "primary" flag and a set of address types;
* **Address** - a basic implementation of AbstractAddress;
* **Country** - encapsulates country attributes (ISO2 and ISO3 codes, a name, a collection of regions);
* **CountryTranslation** - an entity that provides translation for the Country entity;
* **Region** - encapsulates region attributes (the "country+region" combined code, a code, a name, a country entity);
* **RegionTranslation** - an entity that provides translation for the Region entity;
* **AddressType** - describes an address type and includes a type name and a type label. The default types are "billing" and "shipping";
* **AddressTypeTranslation** - an entity that provides translation for the AddressType entity.
