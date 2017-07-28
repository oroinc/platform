<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authorization;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FakeAuthorizationChecker implements AuthorizationCheckerInterface
{
    /**
     * @var array
     * structure: [
     *      'attribute' => (bool) isGranted {true,false}
     * ]
     */
    public $isGrantedMapping = [];

    /**
     * {@inheritdoc}
     */
    public function isGranted($attributes, $object = null)
    {
        if (array_key_exists($attributes, $this->isGrantedMapping)) {
            return $this->isGrantedMapping[$attributes];
        }

        return true;
    }
}
