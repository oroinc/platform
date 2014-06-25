#Default owner for integration related entities

There is possibility to define owner for related entities on integration level.
Default user owner setting included to integration configuration and should be configured during integration creation.

Also _OroIntegrationBundle_ brings helper that could be used by import process to perform populating of integration owner.
It's registered as service `oro_integration.helper.default_owner_helper` and could be easily used as dependency.

####Usage example:

```php

<?php

    use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
    use Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper;

    class ImportStrategy implements StrategyInterface
    {

        /** @var DefaultOwnerHelper */
        protected $defaultOwnerHelper;

        public function __construct(DefaultOwnerHelper $defaultOwnerHelper)
        {
            $this->defaultOwnerHelper = $defaultOwnerHelper;
        }

        // ....

        public function process($remoteEntity)
        {

            // ....

            /** @var object $importedEntity user owner aware entity */
            /** @var Channel $integration could be retrieved from import context */

            $this->defaultOwnerHelper->populateChannelOwner($importedEntity, $integration);

            // ....
        }

        // ....
    }

```
