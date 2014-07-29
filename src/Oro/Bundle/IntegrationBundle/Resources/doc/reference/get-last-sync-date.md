#Get last item sync date

There is possibility to get last synced item date. For this you ought to extend ```Oro\Bundle\IntegrationBundle\Provider\AbstractConnector```
and you ought to redeclare ```public function read()```.

####Usage example:

```php

    public function read()
    {
        # you get current item
        $item = parent::read();

        if (null !== $item) {
            $this->addStatusData('lastSyncItemDate', $this->getStatusData('lastSyncItemDate')));
        }

        $iterator = $this->getSourceIterator();

        #This will cover case when iterator has no items and you will get null on first iteration
        if (!$iterator->valid() && $iterator instanceof SomeInterface) {
            $dateFromReadStarted = $iterator->getStartDate() ? $iterator->getStartDate()->format('Y-m-d H:i:s') : null;

            #there you ought to calculate max date value
            $maxDate = $this->getMaxUpdatedDate($this->getStatusData('lastSyncItemDate'), $dateFromReadStarted);

            $this->addStatusData('lastSyncItemDate', $maxDate);
        }
        return $item;
    }
```

Then you ought to redeclare ```protected function initializeFromContext(ContextInterface $context)```.

####Usage example:
```php

    protected function initializeFromContext(ContextInterface $context)
    {
        parent::initializeFromContext($context);

        #set start date and mode depending on status
        $status      = $this->channel->getStatusesForConnector($this->getType(), Status::STATUS_COMPLETED)->first();
        $iterator    = $this->getSourceIterator();

        if ($iterator instanceof SomeInterface && !empty($status)) {
            $data = $status->getData();
            if (!empty($data['lastSyncItemDate'])) {
                $iterator->setStartDate(new \DateTime($data['lastSyncItemDate']));
            } else {
                $iterator->setStartDate($status->getDate());
            }
        }
    }
```

In the end you will have possibility to start from last item date sync.
