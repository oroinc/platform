#Default owner for channel related entities

There is possibility to define owner for related entities on channel level.
Default user owner setting included to channel configuration and should be configured duting channel creation.

Also _OroIntegrationBundle_ brings helper that could be used by import process to perform populating of channel owner.
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
            /** @var Channel $channel could be retrieved from import context */

            $this->defaultOwnerHelper->populateChannelOwner($importedEntity, $channel);

            // ....
        }

        // ....
    }

```
