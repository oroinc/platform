<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\MutableEntityOverrideProvider;

class MutableEntityOverrideProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var MutableEntityOverrideProvider */
    private $entityOverrideProvider;

    protected function setUp()
    {
        $this->entityOverrideProvider = new MutableEntityOverrideProvider(['Test\Entity1' => 'Test\Entity2']);
    }

    public function testGetSubstituteEntityClassWhenSubstitutionExists()
    {
        self::assertEquals(
            'Test\Entity2',
            $this->entityOverrideProvider->getSubstituteEntityClass('Test\Entity1')
        );
    }

    public function testGetSubstituteEntityClassWhenSubstitutionDoesNotExist()
    {
        self::assertNull(
            $this->entityOverrideProvider->getSubstituteEntityClass('Test\Entity2')
        );
    }

    public function testAddSubstitution()
    {
        $this->entityOverrideProvider->addSubstitution('Test\Entity3', 'Test\Entity4');
        self::assertEquals(
            'Test\Entity4',
            $this->entityOverrideProvider->getSubstituteEntityClass('Test\Entity3')
        );
    }
}
