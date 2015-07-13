<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Domain\Acl as BaseAcl;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

class Acl extends BaseAcl
{
    /** @var [] */
    protected $listeners;

    /** @var [] */
    protected $objectAces;

    /** @var object */
    protected $objectIdentity;

    /**
     * {@inheritdoc}
     */
    public function insertObjectAce(
        SecurityIdentityInterface $sid,
        $mask,
        $index = 0,
        $granting = true,
        $strategy = null
    ) {
        $this->insertAce('objectAces', $index, $mask, $sid, $granting, $strategy);
    }

    /**
     * Inserts an ACE.
     *
     * @param string                    $property
     * @param int                       $index
     * @param int                       $mask
     * @param SecurityIdentityInterface $sid
     * @param bool                      $granting
     * @param string                    $strategy
     *
     * @throws \OutOfBoundsException
     * @throws \InvalidArgumentException
     */
    protected function insertAce($property, $index, $mask, SecurityIdentityInterface $sid, $granting, $strategy = null)
    {
        if ($index < 0 || $index > count($this->$property)) {
            throw new \OutOfBoundsException(
                sprintf('The index must be in the interval [0, %d].', count($this->$property))
            );
        }

        if (!is_int($mask)) {
            throw new \InvalidArgumentException('$mask must be an integer.');
        }

        if (null === $strategy) {
            if (true === $granting) {
                $strategy = PermissionGrantingStrategy::ALL;
            } else {
                $strategy = PermissionGrantingStrategy::ANY;
            }
        }

        $aces = &$this->$property;
        $oldValue = $this->$property;
        if (isset($aces[$index])) {
            $this->$property = array_merge(
                array_slice($this->$property, 0, $index),
                array(true),
                array_slice($this->$property, $index)
            );

            for ($i = $index, $c = count($this->$property) - 1; $i < $c; ++$i) {
                $this->onEntryPropertyChanged($aces[$i + 1], 'aceOrder', $i, $i + 1);
            }
        }

        $aces[$index] = new Entry(null, $this, $sid, $strategy, $mask, $granting, false, false);
        $this->onPropertyChanged($property, $oldValue, $this->$property);
    }

    /**
     * Called when a property of an ACE associated with this ACL changes.
     *
     * @param EntryInterface $entry
     * @param string         $name
     * @param mixed          $oldValue
     * @param mixed          $newValue
     */
    protected function onEntryPropertyChanged(EntryInterface $entry, $name, $oldValue, $newValue)
    {
        foreach ($this->listeners as $listener) {
            $listener->propertyChanged($entry, $name, $oldValue, $newValue);
        }
    }

    /**
     * Called when a property of the ACL changes.
     *
     * @param string $name
     * @param mixed  $oldValue
     * @param mixed  $newValue
     */
    protected function onPropertyChanged($name, $oldValue, $newValue)
    {
        foreach ($this->listeners as $listener) {
            $listener->propertyChanged($this, $name, $oldValue, $newValue);
        }
    }
}