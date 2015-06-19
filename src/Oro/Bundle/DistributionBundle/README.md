OroDistributionBundle
=====================

Implements packages management and bundles load without necessity to update application files.

## Usage ##
Add Resources/config/oro/bundles.yml file to every bundle you want to be autoregistered:

``` yml
bundles:
    - VendorName\Bundle\VendorBundle\VendorAnyBundle
    - My\Bundle\MyBundle\MyCustomBundle
#   - ...
```

That's it! Your bundle (and "VendorAnyBundle") will be automatically registered in AppKernel.php.

## Exclusions ##

```
bundles:
    ...

exclusions:
    - { name: VendorName\Bundle\VendorBundle\VendorAnyBundle }
```

## Routing autoload ##
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


## Precise file reference ##

Symfony 2 allows to refer to file or directory using short name syntax <BundleName>/<FullPath> (for example
OroUserBundle/Controller). However if some new bundle extends existing bundle there is no way to access file from
parent bundle if child bundle has the same file or directory. In OroPlatform developer can access files from precise
bundle by adding "!" sign before bundle name. This feature is extremely useful when there is a need to extend templates
and routing.

Lets assume that AcmeChildBundle extends AcmeParentBundle, and both have directory Controller. In this case:
* AcmeParentBundle/Controller refers to Acme/Bundle/ChildBundle/Controller
* !AcmeParentBundle/Controller refers to Acme/Bundle/ParentBundle/Controller


## Packages management ##
There are console commands 6 console commands: for installing, updating packages, viewing lists of installed in the system projects, packages available for installation and available updates for installed packages

### Installing a package ###

Command syntax is: `oro:package:install [-f|--force] package [version]`
 - `package` - name of the package you want to be installed (`vendor/package`)
 - `version` - version of the package. Optional parameter. If omitted latest available version will be installed base on `minimum-stability` setting of the root `composer.json` file. Version can be specified in any format acceptable by composer (`1.0.2`, `>v0.1, <=0.3`)
 - `--force` - if command run with this option all package dependencies will be installed/updated along with the package. If omitted - command will ask about required packages(if any), process may be either continued or aborted then.

### Update a package ###
Command syntax is: `oro:package:update package`
 - `package` - name of the package you want to be updated (`vendor/package`). Package will be updated to the latest available in repository and acceptable by application version (defined in root `composer.json`).

### Package scripts ###
Along with installing/updating install/update scripts are being executed.
`install.php` - install script of a package
`update_<version>.php` - update script of a package. `<version>` - package version being updated to. Package must contain update scripts of all previous package versions so that migrations could be applied one by one. E.g if package is being update from `v1` to `v3`, then `update_v2.php` and `update_v3.php` will be executed.
**Note:** `v` is not required symbol for update script name (`update_v1.php`). `v` is part of version name. `<version>` is version as it is in package `composer.json`.
Examples:
 -`update_v1.5.php` means that package has version `v1.5` (literally).
 -`update_0.1.1.php` means that there is `0.1.1` version of a package.

### List of installed packages ###
Command syntax is `oro:package:installed`.
Displays currently installed packages (excluding packages from https://packagist.org) along with versions

### List of available packages ###
Command syntax is `oro:package:available`.
Displays packages available in repositories (excluding packages from https://packagist.org)

### List of updates ###
Command syntax is `oro:package:updates`.
Displays available updates for installed packages along with currently installed version and the latest available version.
If package is not stable (package version is `dev-master`) then hash of latest commit is displayed.

