Entity repositories as a services
=================================

EntityBundle provides possibility to define entity repositories as a Symfony DI container services. As a consequence
developer can use entity repositories as regular services - e.g. inject them into other services or inject additional
services into repositories.


### Definition

To define entity repositories as a service user simply should define service with or without a class of an appropriate
repository, use service `oro_entity.abstract_repository` as a parent and pass entity class name as an argument.
To work properly repository class must have default constructor signature
`public function __construct($em, Mapping\ClassMetadata $class)`.

Here is example of repository service definition:

```yml
services:
    oro_product.repository.product:
        class: 'Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository'
        parent: oro_entity.abstract_repository
        arguments:
            - 'Oro\Bundle\ProductBundle\Entity\Product'
```

This is repository for `Oro\Bundle\ProductBundle\Entity\Product` entity, and there are several ways to get this
repository. Developer might get it just as a regular service from container:

```php
$productRepository = $this->container->get('oro_product.repository.product');
```

or get it via ManagerRegistry or DoctrineHelper:

```php
$productRepository = $this->container->get('doctrine')
    ->getManagerForClass('Oro\Bundle\ProductBundle\Entity\Product')
    ->getRepository('Oro\Bundle\ProductBundle\Entity\Product');

$productRepository = $this->container->get('oro_entity.doctrine_helper')
    ->getEntityRepository('Oro\Bundle\ProductBundle\Entity\Product');
```

All these calls will return the same instance of entity repository created via Symfony DI container.


### Decoration

Extension of repositories is a very common case for the application customization - for example, after some new
package was installed repository has to take into account additional conditions (e.g. filtering). The best practice for
this case is usage of repository service decoration.

Here is example of how repository decoration can look like:

```yml
services:
    oro_product.repository.product.new:
        class: 'Oro\Bundle\ProductBundle\Entity\Repository\NewProductRepository'
        parent: oro_entity.abstract_repository
        decorates: oro_product.repository.product
        decoration_inner_name: oro_product.repository.product.original
        arguments:
            - 'Oro\Bundle\ProductBundle\Entity\Product'
        calls:
            - ['setDecoratedRepository', ['@oro_product.repository.product.original']]
```

Here repository service `oro_product.repository.product` (class `Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository`)
is decorated with repository service `oro_product.repository.product.new` (class
`Oro\Bundle\ProductBundle\Entity\Repository\NewProductRepository`), and original repository is injected into decorator
via `setDecoratedRepository` method. Now every time application will request original repository (as a service
`oro_product.repository.product` or by entity class name `Oro\Bundle\ProductBundle\Entity\Product`) instance of
repository decorator will be returned instead of the original repository.


### How it works

Everything is started with abstract service for entity repositories:

```yml
services:
    oro_entity.abstract_repository:
        class: Doctrine\ORM\EntityRepository
        factory: ['@oro_entity.repository.factory', 'getDefaultRepository']
        abstract: true
```

This abstract service is used to create repository instances using entity repository factory service. Also this
service is used as a mark to collect all repository services.

Next important component is compiler pass
`Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityRepositoryCompilerPass`. This compiler pass is used to
collect all repository services by their parent service and build match between entity class name (extracted from the
service argument) and appropriate service ID. Array that contains this match is injected into the entity repository
factory.

And final important component is entity repository factory
`Oro\Bundle\EntityBundle\ORM\Repository\EntityRepositoryFactory` (represented by the service ID
`oro_entity.repository.factory`). This factory implements Doctrine entity repository interface
`Doctrine\ORM\Repository\RepositoryFactory` and it is injected into Doctrine configuration service
`doctrine.orm.configuration` instead of default entity repository. Repository factory accepts Symfony DI container and
array with class names and service IDs as an arguments.

Repository factory has two methods to build repositories - `getDefaultRepository` and `getRepository`.

First method `getDefaultRepository` is called to build original instances of repositories and used in
abstract repository as a factory method. First argument of the method is entity class name (required),
second argument is custom repository class name (optional) and third argument is entity manager (optional).
Repository service class name is automatically passed as a second argument to this method if repository
service has custom class name. Entity manager is passed only during the creation of not service repository.

Second method `getRepository` is an entry point to request a repository and it's used by Doctrine entity managers
to get instances of repositories. According to the repository factory interface is accepts entity manager and entity
class name. This method is responsible for internal caching of repositories and actual repository creation requests.
If entity class name exists in the array of repositories defined as a services then factory gets this repository from
DI container, otherwise it calls method `getDefaultRepository` to get default instance of repository.
