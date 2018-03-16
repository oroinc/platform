## Address Type

Address type is an entity that is used to specify a type of an address. An address can have several address types which are billing and shipping by default.
An address type entity is called AddressType and is stored in Oro/Bundle/AddressBundle/Entity/AddressType.php. It has two properties:
"name" that defines a symbolic name of the type, and "label" that is used in the frontend.
Address types are translatable entities, so their labels should be defined for each supported locale.
Loading and translation of address types are performed in the Oro/Bundle/AddressBundle/Data/ORM/LoadAddressTypeData.php data fixture.

There is an entity called AbstractTypedAddress which is an abstract address entity that extends AbstractAddress and adds the "primary" flag and a set of address types to it.
It has the "types" property and methods to work with it, but the DB relation between an address and an address type must be defined in a specific class:

``` php
/**
 * @var Collection
 *
 * @ORM\ManyToMany(targetEntity="Oro\Bundle\AddressBundle\Entity\AddressType")
 * @ORM\JoinTable(
 *     name="orocrm_contact_address_to_address_type",
 *     joinColumns={@ORM\JoinColumn(name="contact_address_id", referencedColumnName="id")},
 *     inverseJoinColumns={@ORM\JoinColumn(name="type_name", referencedColumnName="name")}
 * )
 **/
protected $types;
```
