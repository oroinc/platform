# OroDistributionBundle

OroDistributionBundle enables application bundles registration based on their YAML configuration files without changing the application files.

## Usage
Add Resources/config/oro/bundles.yml file to every bundle you want to be autoregistered:

``` yml
bundles:
    - VendorName\Bundle\VendorBundle\VendorAnyBundle
    - My\Bundle\MyBundle\MyCustomBundle
#   - ...
```

That's it! Your bundle (and "VendorAnyBundle") will be automatically registered in AppKernel.php.

## Exclusions

```
bundles:
    ...

exclusions:
    - { name: VendorName\Bundle\VendorBundle\VendorAnyBundle }
```

## Routing autoload
Add Resources/config/oro/routing.yml file to every bundle for which you want to autoload its routes.

Add following rule to application's `routing.yml`:

``` yml
oro_auto_routing: # to load bundles
    resource: .
    type:     oro_auto
    
oro_expose:       # to load exposed assets
    resource: .
    type:     oro_expose
```

All routes from your bundles will be imported automatically.
