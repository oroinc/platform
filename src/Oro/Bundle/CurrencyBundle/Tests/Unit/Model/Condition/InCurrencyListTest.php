<?php


namespace Oro\Bundle\CurrencyBundle\Tests\Model\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Model\Condition\InCurrencyList;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Provider\CurrencyListProviderStub;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class InCurrencyListTest extends \PHPUnit\Framework\TestCase
{
    /** @var InCurrencyList */
    private $condition;

    protected function setUp()
    {
        $this->condition = new InCurrencyList(new CurrencyListProviderStub());
    }

    public function testEvaluateSuccess()
    {
        $this->condition->initialize([
            'entity' => MultiCurrency::create(100, 'USD')
        ]);
        $this->assertTrue($this->condition->evaluate(new \stdClass(), new ArrayCollection()));
    }

    public function testEvaluateIncorrectCurrency()
    {
        $this->condition->initialize([MultiCurrency::create(100, 'GBP')]);
        $this->assertFalse(
            $this->condition->evaluate(new \stdClass(), new ArrayCollection()),
            'Unknown currency is used, validation should fail but it is not'
        );
    }

    public function testEvoluteWithIncorrectData()
    {
        try {
            $this->condition->initialize([
                'entity' => new \stdClass()
            ]);
            $this->condition->evaluate(new \stdClass(), new ArrayCollection());

            $this->fail('Right now we only support multycurrency class');
        } catch (InvalidArgumentException $e) {
            $this->assertContains('Entity must be object of', $e->getMessage());
        }
    }

    public function testInitializeWithIncorrectData()
    {
        try {
            $this->condition->initialize(['test' => 'foo']);
            $this->fail('Exception should be thrown if we have no entity option');
        } catch (InvalidArgumentException $e) {
            $this->assertContains('Option "entity" must be set', $e->getMessage());
        }

        try {
            $this->condition->initialize([]);
            $this->fail('Exception should be thrown if we have no options at all');
        } catch (InvalidArgumentException $e) {
            $this->assertContains('Options must have 1 element', $e->getMessage());
        }
    }

    public function testGetName()
    {
        $this->assertEquals(
            'in_currency_list',
            $this->condition->getName(),
            'Name field was changed but be careful and check all workflows before fix this test'
        );
    }
}
