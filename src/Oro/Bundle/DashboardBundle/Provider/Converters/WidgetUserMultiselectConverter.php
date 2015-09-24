<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Class WidgetUserMultiselectConverter
 * @package Oro\Bundle\DashboardBundle\Provider\Converters
 */
class WidgetUserMultiselectConverter extends ConfigValueConverterAbstract
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param array $converterAttributes
     * @param mixed $value
     * @return mixed
     */
    public function getFormValue(array $converterAttributes, $value)
    {
        return $this->getUsersFullData($value);
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function getViewValue($value)
    {
        $users = $this->getUsersFullData($value);

        $names = [];
        /** @var User $user */
        foreach ($users as $user) {
            $names[] = $user->getFirstName() . ' ' . $user->getLastName();
        }

        return implode('; ', $names);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function getUsersFullData($value)
    {
        $value = is_array($value) ? $value : array($value);

        $ids = [];
        foreach ($value as $item) {
            if (is_object($item)) {
                $ids[] = $item->getId();
            }
        }

        return $this->entityManager->getRepository('OroUserBundle:User')->findById($ids);
    }
}
