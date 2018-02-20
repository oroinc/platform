<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Migration\CleanupCampaignMigration;
use Oro\Bundle\EntityExtendBundle\Migration\CleanupMarketingListMigration;
use Oro\Bundle\EntityExtendBundle\Migration\CleanupTrackingMigration;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class CleanupMarketingPostUpMigrationListener
{
    const TRACKING_BUNDLE_NAME = 'OroTrackingBundle';
    const MARKETING_LIST_BUNDLE_NAME = 'OroMarketingListBundle';
    const CAMPAIGN_BUNDLE_NAME = 'OroCampaignBundle';

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * Constructor.
     *
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * POST UP event handler
     *
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        if (!$this->isBundleEnabled(self::TRACKING_BUNDLE_NAME)) {
            $event->addMigration(new CleanupTrackingMigration());
        }
        if (!$this->isBundleEnabled(self::MARKETING_LIST_BUNDLE_NAME)) {
            $event->addMigration(new CleanupMarketingListMigration());
        }
        if (!$this->isBundleEnabled(self::CAMPAIGN_BUNDLE_NAME)) {
            $event->addMigration(new CleanupCampaignMigration());
        }
    }

    /**
     * @param string $bundleName
     *
     * @return bool
     */
    protected function isBundleEnabled($bundleName)
    {
        try {
            $bundle = $this->kernel->getBundle($bundleName);
            if (!($bundle instanceof BundleInterface || is_array($bundle))) {
                return false;
            }
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return true;
    }
}
