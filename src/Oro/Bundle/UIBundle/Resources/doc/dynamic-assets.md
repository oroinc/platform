Dynamic Assets
==============

Sometime assets can be changed during an application life cycle, for instance when an administrator does some configuration of a site. When such situation happens the assets cache should be busted properly. Unfortunately Symfony does not manage this case out of the box. But, fortunately, the [Asset Component](http://symfony.com/doc/current/components/asset/introduction.html) can be easily enhanced to support this feature.

The following samples of code show how to add dynamic versioning for any asset package.

Lets suppose that `acme` asset package should use the dynamic versioning.

At first the package should be registered. You can use `Resources/config/oro/app.yml` in your bundle or `config/config.yml`:

```yaml
framework:
    assets:
        packages:
            acme:
                version: %assets_version%
                version_format: ~ # use the default format
```

The next step is to set [DynamicAssetVersionStrategy](../../Asset/DynamicAssetVersionStrategy.php) for this package. It can be done be DI compiler pass:

```php
<?php

namespace Acme\Bundle\SomeBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\DynamicAssetVersionPass;

class AcmeSomeBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new DynamicAssetVersionPass('acme'));
    }
}
```

That's all, the configuration is finished.

Now you need to take care about updating of the package version when your assets are changed. The following code shows how to update the version:

```php
<?php

namespace Acme\Bundle\SomeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SomeController extends Controller
{
    public function updateAction()
    {
        ...

        /** @var Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager $assetVersionManager */
        $assetVersionManager = $this->get('oro_ui.dynamic_asset_version_manager');
        $assetVersionManager->updateAssetVersion('acme');

        ...
    }
}
```

The usage of your assets is the same as other assets, for example by the well-known `asset()` Twig function:

```twig
{{ asset('test.js', 'acme') }}
{# the result may be something like this: test.js?version=123-2 #}
{# where #}
{# '123' is the static asset version specified in %assets_version% parameter #}
{# '2' is the dynamic asset version; this number is increased each time you call $assetVersionManager->updateAssetVersion('acme') #}
```

Please pay attention that the package name should be passed to the `asset()` function. This tells Symfony that the asset belongs your package and the dynamic versioning strategy should be applied.
