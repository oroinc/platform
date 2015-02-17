<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\IntegrationBundle\Entity\Status;

class LoadStatusData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $data = [
        [
            'channel'   => 'oro_integration:foo_integration',
            'status'    => Status::STATUS_COMPLETED,
            'connector' => 'first_connector',
            'date'      => '2015-02-01 00:00:00',
            'message'   => '',
            'reference' => 'oro_integration:foo_first_connector_first_status_completed'
        ],
        [
            'channel'   => 'oro_integration:foo_integration',
            'status'    => Status::STATUS_COMPLETED,
            'connector' => 'first_connector',
            'date'      => '2015-02-01 00:05:00',
            'message'   => '',
            'reference' => 'oro_integration:foo_first_connector_second_status_completed'
        ],
        [
            'channel'   => 'oro_integration:foo_integration',
            'status'    => Status::STATUS_FAILED,
            'connector' => 'first_connector',
            'date'      => '2015-02-01 00:10:00',
            'message'   => '',
            'reference' => 'oro_integration:foo_first_connector_third_status_failed'
        ],
        [
            'channel'   => 'oro_integration:foo_integration',
            'status'    => Status::STATUS_COMPLETED,
            'connector' => 'second_connector',
            'date'      => '2015-02-01 00:15:00',
            'message'   => '',
            'reference' => 'oro_integration:foo_second_connector_first_status_completed'
        ],
        [
            'channel'   => 'oro_integration:bar_integration',
            'status'    => Status::STATUS_COMPLETED,
            'connector' => 'first_connector',
            'date'      => '2015-02-01 00:20:00',
            'message'   => '',
            'reference' => 'oro_integration:bar_first_connector_first_status_completed'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $data) {
            $status = new Status();
            $status->setChannel($this->getReference($data['channel']));
            $status->setCode($data['status']);
            $status->setConnector($data['connector']);
            $status->setDate(new \DateTime($data['date'], new \DateTimeZone('UTC')));
            $status->setMessage($data['message']);

            $this->setReference($data['reference'], $status);

            $manager->persist($status);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadChannelData'
        ];
    }
}
