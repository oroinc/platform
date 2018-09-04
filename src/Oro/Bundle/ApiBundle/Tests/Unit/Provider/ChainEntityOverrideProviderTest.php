<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ChainEntityOverrideProvider;
use Oro\Bundle\ApiBundle\Provider\MutableEntityOverrideProvider;

class ChainEntityOverrideProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChainEntityOverrideProvider */
    private $chainEntityOverrideProvider;

    protected function setUp()
    {
        $this->chainEntityOverrideProvider = new ChainEntityOverrideProvider([
            new MutableEntityOverrideProvider([
                'Test\Entity1' => 'Test\Model1_1',
                'Test\Entity2' => 'Test\Model2_1'
            ]),
            new MutableEntityOverrideProvider([
                'Test\Entity2' => 'Test\Model2_2',
                'Test\Entity3' => 'Test\Model3_2'
            ])
        ]);
    }

    public function testGetSubstituteEntityClassWhenSubstitutionExistsInFirstProvider()
    {
        self::assertEquals(
            'Test\Model1_1',
            $this->chainEntityOverrideProvider->getSubstituteEntityClass('Test\Entity1')
        );
    }

    public function testGetSubstituteEntityClassWhenSubstitutionExistsInBothProviders()
    {
        self::assertEquals(
            'Test\Model2_1',
            $this->chainEntityOverrideProvider->getSubstituteEntityClass('Test\Entity2')
        );
    }

    public function testGetSubstituteEntityClassWhenSubstitutionExistsInSecondProvider()
    {
        self::assertEquals(
            'Test\Model3_2',
            $this->chainEntityOverrideProvider->getSubstituteEntityClass('Test\Entity3')
        );
    }

    public function testGetSubstituteEntityClassWhenSubstitutionDoesNotExist()
    {
        self::assertNull(
            $this->chainEntityOverrideProvider->getSubstituteEntityClass('Test\Entity4')
        );
    }
}
