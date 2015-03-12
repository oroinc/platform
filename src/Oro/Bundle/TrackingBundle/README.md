OroTrackingBundle
=================
Provides:
    - CRUD for configuring web tracking
    - Proxying tracking data to Piwik (in case it's enabled) 
    - Web events tracking functionality
    - Tracking event data parsing
    - Finding identifying objects by provided criteria
    - Assigning tracking with identified objects

# Notes
-------

In case when Piwik synchronization enabled tracking website's "identifier" fields value
should be the same as Piwik website id (integer value).

# TrackingProcessor
-------------------

The main goal of processing(parsing) tracking events is to identify object(s) for which event(s) belongs to.
For example, it can be identification of users/customers form any integrated system like eCommerce, blog,
project management application, etc.

## How it works.

- The start point is command "oro:cron:tracking:parse" that process web events (table "oro_tracking_event"), 
  events data (table "oro_tracking_data") and fills tables "oro_tracking_visit" and "oro_tracking_visit_event".
  At the same time it collects web tracking event's names and fills dictionary that represented with table
  "oro_tracking_visit_dictionary". All this things is done because tracking data comes from outside as JSON,
  so we can't guarantee identification and future reports, segments or charts building will be fast enough.
  So, to avoid bottle necks we are optimizing data structure.

- This command can be executed manually via command line.
  By default it will be executed every 15mins via JobQueue (cronjob).

- The next stage is identification. It represented with "**TrackingEventIdentificationProvider**" which is chain
  provider and service "**oro_tracking.provider.identifier_provider**". You can implement own identification provider
  for your purposes. The only requirement - it should implement "**TrackingEventIdentifierInterface**" and be registered
  in services with tag "**oro_tracking.provider.identification**". Also you can prioritise your provider with priority
  parameter.
  Please note, that the input data for such provider is "**TrackingVisit**" object.

## Example

Fully working code you can find in OroCRM MagentoBundle -> TrackingCustomerIdentification

###Services:

```yaml

    acme_test.provider.tracking_customer_identificator:
        class: %acme_test.provider.tracking_customer_identificator.class%
        arguments:
           - @doctrine
           - @oro_entity_config.provider.extend
           - @orocrm_channel.provider.settings_provider
        tags:
           - {name: oro_tracking.provider.identification, priority: 10}
```

###Code:

``` php

namespace Acme\Bundle\TestBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;
use Oro\Bundle\TrackingBundle\Provider\TrackingEventIdentifierInterface;
use OroCRM\Bundle\ChannelBundle\Entity\Channel;
use OroCRM\Bundle\ChannelBundle\Provider\SettingsProvider;

class TestCustomerIdentification implements TrackingEventIdentifierInterface
{
    /** @var ObjectManager */
    protected $em;

    /** @var  ConfigProvider */
    protected $extendConfigProvider;

    /** @var  SettingsProvider */
    protected $settingsProvider;

    /**
     * @param Registry         $doctrine
     * @param ConfigProvider   $extendConfigProvider
     * @param SettingsProvider $settingsProvider
     */
    public function __construct(
        Registry $doctrine,
        ConfigProvider $extendConfigProvider,
        SettingsProvider $settingsProvider
    ) {
        $this->em                   = $doctrine->getManager();
        $this->extendConfigProvider = $extendConfigProvider;
        $this->settingsProvider     = $settingsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(TrackingVisit $trackingVisit)
    {
        $hasTrackingWebsiteChannel = $this->extendConfigProvider->hasConfig(
            'Oro\Bundle\TrackingBundle\Entity\TrackingWebsite',
            'channel'
        );

        if ($hasTrackingWebsiteChannel) {
            $trackingWebsite = $trackingVisit->getTrackingWebsite();
            if (method_exists($trackingWebsite, 'getChannel')) {
                /** @var Channel $channel */
                $channel = $trackingWebsite->getChannel();
                $type    = $channel ? $channel->getChannelType() : false;

                if ($type && $type === ChannelType::TYPE) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
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

            $target = $this->em->getRepository($this->getTarget())->findOneBy(['originId' => $userIdentifier]);
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
    public function getTarget()
    {
        return $this->settingsProvider->getCustomerIdentityFromConfig(ChannelType::TYPE);
    }

    /**
     * Parse user identifier string and returns PK value by which identity object can be retrived.
     * Returns null in case identifier is not found.
     *
     * @param string $identifier
     *
     * @return string|null
     */
    protected function parse($identifier = null)
    {
        if (!empty($identifier)) {
            $identifierArray = explode('; ', $identifier);
            $identifierData  = [];
            array_walk(
                $identifierArray,
                function ($string) use (&$identifierData) {
                    $data = explode('=', $string);
                    $identifierData[$data[0]] = $data[1];
                }
            );

            if (array_key_exists('id', $identifierData) && $identifierData['id'] !== 'guest') {
                return $identifierData['id'];
            }
        }

        return null;
    }
}
```
