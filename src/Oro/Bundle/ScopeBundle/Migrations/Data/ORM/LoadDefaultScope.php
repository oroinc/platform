<?php

namespace Oro\Bundle\ScopeBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * Data fixture that loads the default scope into the database.
 *
 * This fixture creates and persists a default {@see Scope} entity during the data loading phase
 * of application installation or migration. The default scope serves as the base scope
 * context for the application and is used as a fallback when no specific scope is
 * configured. This fixture ensures that the application always has at least one scope
 * available for scope-aware operations.
 */
class LoadDefaultScope extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager)
    {
        $scope = new Scope();
        $manager->persist($scope);
        $manager->flush();
    }
}
