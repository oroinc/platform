OroTrackingBundle
=================
Provides:

    - CRUD for configuring web tracking
    - Proxying tracking data to Piwik (in case it's enabled) 
    - Web events tracking functionality
    - Tracking event data parsing
    - Finding identifying objects by provided criteria
    - Assigning tracking with identified objects
    - Assigning tracking with platform data objects
    - Ability to create reports based on tracked data

# Notes

In case when Piwik synchronization enabled tracking website's "identifier" fields value should be the same as Piwik website id (integer value).

# TrackingProcessor

The main goal of processing(parsing) tracking events is to identify object(s) for which event(s) belongs to. For example, it can be identification of users/customers form any integrated system like eCommerce, blog, project management application, etc.

## How it works.

- Web events are collected using tracking.php front controller, using another HTTP request to prod application with all request data.
- TrackingDataController launch new import job "import_request_to_database" with all data from query ($request->query->all())
- `Oro\Bundle\TrackingBundle\ImportExport\DataConverter` tranforms that data to match TrackingData
- `Oro\Bundle\TrackingBundle\Entity\TrackingEvent` will be created as a relation from `Oro\Bundle\TrackingBundle\Entity\TrackingData` by `Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer`
- Tracking data saved by writer to db
- Then command "oro:cron:tracking:parse" started by cron, and use `Oro\Bundle\TrackingBundle\Processor\TrackingProcessor` to process web events.
- `Oro\Bundle\TrackingBundle\Processor\TrackingProcessor` read data from table "oro_tracking_event", events data table "oro_tracking_data", and calls `Oro\Bundle\TrackingBundle\Provider\TrackingEventIdentificationProvider` (chain provider that use another concrete providers) to fill tables "oro_tracking_visit" and "oro_tracking_visit_event". 
- At the same time it collects web tracking event's names and fills dictionary that represented with table "oro_tracking_event_dictionary". All this things is done because tracking data comes from outside as JSON, so we can't guarantee identification and future reports, segments or charts building will be fast enough. So, to avoid bottle necks we are optimizing data structure.

- This command can be executed manually via command line. By default it will be executed every 15mins via JobQueue (cronjob).

- The next stage is identification. It represented with "**TrackingEventIdentificationProvider**" which is chain provider and service "**oro_tracking.provider.identifier_provider**". You can implement own identification provider for your purposes. The only requirement - it should implement "**TrackingEventIdentifierInterface**" and be registered in services with tag "**oro_tracking.provider.identification**". Also you can prioritise your provider with priority parameter.

- Please note, that the input data for such provider is "**TrackingVisit**" object.

- To connect tracking event with your data, provider should have 3 additional methods: **isApplicableVisitEvent**, **processEvent**, **getEventTargets**

## Request parameters expected by tracking

Actuall mapping can be seen in `Oro\Bundle\TrackingBundle\ImportExport\DataConverter`,
can be filled by Piwik automatically, otherwise client should fill them in custom code.

* e_n - event name
* e_v - event value
* action_name
* idsite - site id
* _uid - user identifier
* _rcn - code (e.g. Campaign code)
* _id - visit id, required, should represent unique visit id


## Example

Fully working code you can find in OroCRM MagentoBundle -> TrackingCustomerIdentification
As a simple example, it will looks like this:

###Services:

```yaml

    acme_test.provider.tracking_customer_identificator:
        class: %acme_test.provider.tracking_customer_identificator.class%
        tags:
           - {name: oro_tracking.provider.identification, priority: 10}
```

###Code:

``` php

namespace Acme\Bundle\TestBundle\Provider;

use ...

class TestCustomerIdentification implements TrackingEventIdentifierInterface
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(TrackingVisit $trackingVisit)
    {
        /**
         * Here we checks if given tracking visit can be identified by our provider.
         */
        if (...) {
            return true;
        }

        return false;
    }

    /**
     * The main logic, in most cases it will be the same.
     *
     * {@inheritdoc}
     */
    public function identify(TrackingVisit $trackingVisit)
    {
        $userIdentifier = $trackingVisit->getParsedUID() > 0
            ? $trackingVisit->getParsedUID()
            : $this->parse($trackingVisit->getUserIdentifier());
        if ($userIdentifier) {
            $result = [
                'parsedUID'    => $userIdentifier,
                'targetObject' => null
            ];

            $target = $this->em->getRepository($this->getTarget())->findOneBy([ {columnName} => $userIdentifier ]);
            if ($target) {
                $result['targetObject'] = $target;
            }

            return $result;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentityTarget()
    {
        /**
         * Here we should return object's class name for which given tracking visit will be assigned to.
         */
    }
    
    /**
     * {@inheritdoc}
     */
    public function isApplicableVisitEvent(TrackingVisitEvent $trackingVisitEvent)
    {
        /**
         * should return true if this processor can process given visit event
         */
    }
    
    /**
     * {@inheritdoc}
     */
    public function processEvent(TrackingVisitEvent $trackingVisitEvent)
    {
        /**
         *  Here should be some logic that returns array with target entity classes
         */
    }

    /**
     * {@inheritdoc}
     */
    public function getEventTargets()
    {
        /**
         *  Should return array with necessary event targets 
         */
    }

    /**
     * Parse user identifier string and returns value by which identity object can be retrieved.
     * Returns null in case identifier is not found.
     *
     * @param string $identifier
     *
     * @return string|null
     */
    protected function parse($identifier = null)
    {
        if (!empty($identifier)) {
            /**
             * Actually parser for user identifier string
             */
        }

        return null;
    }
}
```

# Tracked data in report builder

User can crate reports based on tracked event data.

The main entity for this data is **Visitor event** - parsed web event data related to customer, campaign order or other customer data. This entity have next fields:

 - **Type**. Virtual string field. Type of event. Each tracking website can use own list of event types.
 
 - **IP**. Virtual string field. IP address of visitor
 
 - **URL**. Virtual string field. URL action comes from.
 
 - **Title**.  Virtual string field. Title of page action comes from.
  
 - **Bot**. Virtual boolean field. Shows is visitor is bot
  
 - **Client name**.  Virtual string field. Visitor client name (e.g., Firefox, Chrome)
  
 - **Client type**. Virtual string field. Visitor client type (e.g., Browser)
  
 - **Client version**. Virtual string field. Version number of visitor's client.
 
 - **OS**. Virtual string field. Visitor's operating system name. (e.g., Windows, Mac)
 
 - **OS version**. Virtual string field. Visitor's operating system name.(e.g., XP, 10.10)
 
 - **Desktop**. Virtual boolean field. True if visitor comes from desktop system 
 
 - **Mobile**. Virtual boolean field. True if visitor comes from mobile system 
 
 - **Identified**. Virtual boolean field. True if visitor was detected. (Non anonymous event) 
 
 - **Event date**. Virtual datetime field. Date than event was executed
 
 - **Tracking website** Link to website tracking config record.
 
 - List of connected records to the event event entity.

Additionally, there is **Tracking Event** table - original web event data recorded from the website
