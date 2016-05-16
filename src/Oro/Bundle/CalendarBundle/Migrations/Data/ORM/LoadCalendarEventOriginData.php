<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class LoadCalendarEventOriginData extends AbstractFixture
{
    /** @var array */
    protected $originEnumData = [
        CalendarEvent::ORIGIN_CLIENT   => [
            'label'    => 'Client',
            'priority' => 1,
            'default'  => false
        ],
        CalendarEvent::ORIGIN_SERVER   => [
            'label'    => 'Server',
            'priority' => 2,
            'default'  => true
        ],
        CalendarEvent::ORIGIN_EXTERNAL => array(
            'label'    => 'External',
            'priority' => 3,
            'default'  => false
        )
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadData($manager, CalendarEvent::ORIGIN_ENUM_CODE, $this->originEnumData);
    }

    /**
     * @param ObjectManager $manager
     * @param string        $enumCode
     * @param array         $data
     */
    protected function loadData(ObjectManager $manager, $enumCode, $data)
    {
        $entityName = ExtendHelper::buildEnumValueClassName($enumCode);

        /** @var EnumValueRepository $enumRepository */
        $enumRepository = $manager->getRepository($entityName);
        $existingValues = $enumRepository->findAll();
        $existingCodes  = [];

        /** @var AbstractEnumValue $existingValue */
        foreach ($existingValues as $existingValue) {
            $existingCodes[$existingValue->getId()] = true;
        }

        foreach ($data as $key => $value) {
            if (!isset($existingCodes[$key])) {
                $enum = $enumRepository->createEnumValue(
                    $value['label'],
                    $value['priority'],
                    $value['default'],
                    $key
                );

                $existingCodes[$key] = true;
                $manager->persist($enum);
            }
        }

        $manager->flush();
    }
}
