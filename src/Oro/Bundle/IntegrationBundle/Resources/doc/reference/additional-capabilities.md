# Additional capabilities

## Table of content

* [Save service data between synchronizations](#save-service-data-between-synchronizations)

### Save service data between synchronizations

If connector of your integration requires to store some data between imports, status entity could be used for this purposes.
That's might be useful for example when integration supports multiple modes(update/initial import) and need to store
date of last synchronization or another example if your connector supports renew download it's useful to store current state.

To use this feature your connector class should extends `Oro\Bundle\IntegrationBundle\Provider\AbstractConnector`,
and then methods `addStatusData` and `getStatusData` will be available.

**Example:**
``` php

    // your connector class
    // ...
    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $item = parent::read();

        // store last item updated at
        if (null !== $item && !$this->getSourceIterator()->valid()) {
            $this->addStatusData('lastItemUpdatedAt', $item['updated_at']);
        }
        
        return $item;
    }
    // ...


    // retrieve data from status
    $status = $this->container->get('doctrine')->getRepository('OroIntegrationBundle:Channel')
        ->getLastStatusForConnector($channel, $this->getType(), Status::STATUS_COMPLETED);
    /** @var array **/
    $data = $status->getData();
    $lastItemUpdatedAt = $data['lastItemUpdatedAt'];
```
