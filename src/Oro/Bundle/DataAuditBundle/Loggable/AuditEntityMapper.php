<?php

namespace Oro\Bundle\DataAuditBundle\Loggable;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

class AuditEntityMapper
{
    /**
     * @var array
     *  [
     *      user class => [
     *          'entry'       => audit entity class,
     *          'field_entry' => audit field entity class
     *      ],
     *      ...
     *  ]
     */
    private $map = [];

    /**
     * @param string $userClass
     * @param string $auditEntryClass
     * @param string $auditEntryFieldClass
     */
    public function addAuditEntryClasses($userClass, $auditEntryClass, $auditEntryFieldClass)
    {
        $this->map[$userClass] = [
            'entry'       => $auditEntryClass,
            'field_entry' => $auditEntryFieldClass
        ];
    }

    /**
     * @param string $userClass
     * @param string $auditEntryClass
     */
    public function addAuditEntryClass($userClass, $auditEntryClass)
    {
        $this->map[$userClass]['entry'] = $auditEntryClass;
    }

    /**
     * @param string $userClass
     * @param string $auditEntryFieldClass
     */
    public function addAuditEntryFieldClass($userClass, $auditEntryFieldClass)
    {
        $this->map[$userClass]['field_entry'] = $auditEntryFieldClass;
    }

    /**
     * @param AbstractUser|null $user
     *
     * @return string
     */
    public function getAuditEntryClass(AbstractUser $user = null)
    {
        if (null === $user) {
            return $this->getDefaultEntry('entry');
        }

        $userClass = ClassUtils::getClass($user);
        if (empty($this->map[$userClass]['entry'])) {
            throw new \InvalidArgumentException(sprintf('Audit entry not found for "%s"', $userClass));
        }

        return $this->map[$userClass]['entry'];
    }

    /**
     * @param AbstractUser|null $user
     *
     * @return string
     */
    public function getAuditEntryFieldClass(AbstractUser $user = null)
    {
        if (null === $user) {
            return $this->getDefaultEntry('field_entry');
        }

        $userClass = ClassUtils::getClass($user);
        if (empty($this->map[$userClass]['field_entry'])) {
            throw new \InvalidArgumentException(sprintf('Audit entry field not found for "%s"', $userClass));
        }

        return $this->map[$userClass]['field_entry'];
    }

    /**
     * @param string $auditEntryClass
     *
     * @return string
     */
    public function getAuditEntryFieldClassForAuditEntry($auditEntryClass)
    {
        $auditEntryFieldClass = null;
        foreach ($this->map as $item) {
            if (array_key_exists('entry', $item) && $item['entry'] === $auditEntryClass) {
                if (array_key_exists('field_entry', $item)) {
                    $auditEntryFieldClass = $item['field_entry'];
                }
                break;
            }
        }
        if (!$auditEntryFieldClass) {
            throw new \InvalidArgumentException(
                sprintf('Audit entry field not found for "%s"', $auditEntryClass)
            );
        }

        return $auditEntryFieldClass;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function getDefaultEntry($key)
    {
        $firstItem = reset($this->map);

        return $firstItem[$key];
    }
}
