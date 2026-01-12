<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Migration\CleanupCampaignMigration;
use Oro\Bundle\EntityExtendBundle\Migration\CleanupMarketingListMigration;
use Oro\Bundle\EntityExtendBundle\Migration\CleanupTrackingMigration;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Handles post-migration cleanup for removed marketing-related bundles.
 *
 * This listener is triggered after database migrations are applied. It checks if certain
 * marketing-related bundles (Tracking, MarketingList, Campaign) are enabled in the application.
 * If a bundle is not enabled, it schedules a cleanup migration to remove the associated
 * database tables and configurations for that bundle.
 */
class CleanupMarketingPostUpMigrationListener
{
    public const TRACKING_BUNDLE_NAME = 'OroTrackingBundle';
    public const MARKETING_LIST_BUNDLE_NAME = 'OroMarketingListBundle';
    public const CAMPAIGN_BUNDLE_NAME = 'OroCampaignBundle';

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * Constructor.
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * POST UP event handler
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
