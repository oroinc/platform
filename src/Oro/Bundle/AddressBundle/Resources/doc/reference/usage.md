## Usage

### PHP API

``` php
<?php
    //Accessing address manager from controller
    /** @var  $addressManager \Oro\Bundle\AddressBundle\Entity\Manager\AddressManager */
    $addressManager = $this->get('oro_address.address.provider')->getStorage();

    //create empty address entity
    $address = $addressManager->createAddress();

    //process insert/update
    $this->get('oro_address.form.handler.address')->process($entity)

    //accessing address form service
    $this->get('oro_address.form.address')
```

### Address collection

Address collection may be added to a form following the next three steps:

1) Add a field with the oro_address_collection type to a form:

```php
$builder->add(
    'addresses',
    'oro_address_collection',
    array(
        'required' => false,
        'type'     => 'oro_address'
    )
);
```

2) Add AddressCollectionTypeSubscriber. AddressCollectionTypeSubscriber must be initialized with an address collection field name and an address class name.

```php
$builder->addEventSubscriber(new AddressCollectionTypeSubscriber('addresses', $this->addressClass));
```

3) Add OroAddressBundle:Include:fields.html.twig to the template to enable address form field types.

```php
{% form_theme form with ['OroAddressBundle:Include:fields.html.twig']}
```
