<?php
namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class AutoResponseListener extends MailboxEmailListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @var ServiceLink
     */
    private $autoResponseManagerLink;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @param ServiceLink              $autoResponseManagerLink
     * @param MessageProducerInterface $producer
     */
    public function __construct(ServiceLink $autoResponseManagerLink, MessageProducerInterface $producer)
    {
        $this->autoResponseManagerLink = $autoResponseManagerLink;
        $this->producer = $producer;
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $emailIds = $this->popEmailIds();
        if (!$emailIds) {
            return;
        }
        
        $this->producer->send(Topics::SEND_AUTO_RESPONSES, [
            'ids' => $emailIds,
        ]);
    }

    /**
     * @return array
     */
    protected function popEmailIds()
    {
        $emailIds = array_map(
            function (EmailBody $emailBody) {
                return $emailBody->getEmail()->getId();
            },
            array_filter(
                $this->emailBodies,
                function (EmailBody $emailBody) {
                    return $this->getAutoResponseManager()->hasAutoResponses($emailBody->getEmail());
                }
            )
        );
        $this->emailBodies = [];

        return array_values($emailIds);
    }

    /**
     * @return AutoResponseManager
     */
    protected function getAutoResponseManager()
    {
        return $this->autoResponseManagerLink->getService();
    }
}
