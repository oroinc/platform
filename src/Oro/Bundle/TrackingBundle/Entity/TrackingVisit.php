<?php


namespace Oro\Bundle\TrackingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\TrackingBundle\Model\ExtendTrackingVisit;

use OroCRM\Bundle\MagentoBundle\Entity\Customer;

/**
 * @ORM\Table(name="oro_tracking_visit")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *  defaultValues={
 *      "entity"={
 *          "icon"="icon-external-link"
 *      }
 *  }
 * )
 */
class TrackingVisit extends ExtendTrackingVisit
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Customer
     * @ORM\ManyToOne(targetEntity="OroCRM\Bundle\MagentoBundle\Entity\Customer", cascade={"persist"})
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     */
    protected $customer;

    /**
     * @var string
     *
     * @ORM\Column(name="visitor", type="string", length=255)
     */
    protected $visitor;

    /**
     * @var string
     *
     * @ORM\Column(name="user_identifier", type="string", length=255)
     */
    protected $userIdentifier;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="first_action_time", type="datetime")
     */
    protected $firstActionTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_action_time", type="datetime")
     */
    protected $lastActionTime;

    /**
     * @var string
     *
     * @ORM\Column(name="parsed_uid", type="integer", length=255)
     */
    protected $parsedUID;

    /**
     * @var integer
     *
     * @ORM\Column(name="parsing_count", type="integer", nullable=true)
     */
    protected $parsingCount;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=255, nullable=true)
     */
    protected $ip;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getVisitor()
    {
        return $this->visitor;
    }

    /**
     * @param string $visitor
     */
    public function setVisitor($visitor)
    {
        $this->visitor = $visitor;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return string
     */
    public function getUserIdentifier()
    {
        return $this->userIdentifier;
    }

    /**
     * @param string $userIdentifier
     */
    public function setUserIdentifier($userIdentifier)
    {
        $this->userIdentifier = $userIdentifier;
    }

    /**
     * @return \DateTime
     */
    public function getFirstActionTime()
    {
        return $this->firstActionTime;
    }

    /**
     * @param \DateTime $firstActionTime
     */
    public function setFirstActionTime($firstActionTime)
    {
        $this->firstActionTime = $firstActionTime;
    }

    /**
     * @return \DateTime
     */
    public function getLastActionTime()
    {
        return $this->lastActionTime;
    }

    /**
     * @param \DateTime $lastActionTime
     */
    public function setLastActionTime($lastActionTime)
    {
        $this->lastActionTime = $lastActionTime;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;
    }

    /**
     * @return int
     */
    public function getParsingCount()
    {
        return $this->parsingCount;
    }

    /**
     * @param int $parsingCount
     */
    public function setParsingCount($parsingCount)
    {
        $this->parsingCount = $parsingCount;
    }

    /**
     * @return string
     */
    public function getParsedUID()
    {
        return $this->parsedUID;
    }

    /**
     * @param string $parsedUID
     */
    public function setParsedUID($parsedUID)
    {
        $this->parsedUID = $parsedUID;
    }
}
