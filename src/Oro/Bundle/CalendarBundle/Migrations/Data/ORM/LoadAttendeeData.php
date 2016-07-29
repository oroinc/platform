<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class LoadAttendeeData extends AbstractFixture
{
    /** @var array */
    protected $statusEnumData = [
        Attendee::STATUS_NONE      => [
            'label'    => 'None',
            'priority' => 1,
            'default'  => true
        ],
        Attendee::STATUS_ACCEPTED  => [
            'label'    => 'Accepted',
            'priority' => 2,
            'default'  => false
        ],
        Attendee::STATUS_DECLINED  => [
            'label'    => 'Declined',
            'priority' => 3,
            'default'  => false
        ],
        Attendee::STATUS_TENTATIVE => [
            'label'    => 'Tentative',
            'priority' => 4,
            'default'  => false
        ]
    ];

    /** @var array */
    protected $typeEnumData = [
        Attendee::TYPE_ORGANIZER => [
            'label'    => 'Organizer',
            'priority' => 1,
            'default'  => true
        ],
        Attendee::TYPE_OPTIONAL  => [
            'label'    => 'Optional',
            'priority' => 2,
            'default'  => false
        ],
        Attendee::TYPE_REQUIRED  => [
            'label'    => 'Required',
            'priority' => 3,
            'default'  => false
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadData($manager, Attendee::STATUS_ENUM_CODE, $this->statusEnumData);
        $this->loadData($manager, Attendee::TYPE_ENUM_CODE, $this->typeEnumData);
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
