<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\Extra\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigSection;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigContextTest extends TestCase
{
    private ConfigContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new ConfigContext();
    }

    public function testClassName(): void
    {
        $this->context->setClassName('test');
        self::assertEquals('test', $this->context->getClassName());
        self::assertEquals('test', $this->context->get('class'));
    }

    public function testTargetAction(): void
    {
        self::assertNull($this->context->getTargetAction());

        $this->context->setTargetAction('test');
        self::assertEquals('test', $this->context->getTargetAction());
        self::assertEquals('test', $this->context->get('targetAction'));
    }

    public function testIsCollection(): void
    {
        self::assertFalse($this->context->isCollection());

        $this->context->setIsCollection(true);
        self::assertTrue($this->context->isCollection());
        self::assertTrue($this->context->get('collection'));
    }

    public function testParentClassName(): void
    {
        self::assertNull($this->context->getParentClassName());

        $this->context->setParentClassName('test');
        self::assertEquals('test', $this->context->getParentClassName());
        self::assertEquals('test', $this->context->get('parentClass'));
    }

    public function testAssociationName(): void
    {
        self::assertNull($this->context->getAssociationName());

        $this->context->setAssociationName('test');
        self::assertEquals('test', $this->context->getAssociationName());
        self::assertEquals('test', $this->context->get('association'));
    }

    public function testMaxRelatedEntities(): void
    {
        self::assertNull($this->context->getMaxRelatedEntities());

        $this->context->setMaxRelatedEntities(123);
        self::assertEquals(123, $this->context->getMaxRelatedEntities());
        self::assertEquals(123, $this->context->get('maxRelatedEntities'));

        $this->context->setMaxRelatedEntities(null);
        self::assertNull($this->context->getMaxRelatedEntities());
        self::assertFalse($this->context->has('maxRelatedEntities'));
    }

    public function testRequestedExclusionPolicy(): void
    {
        self::assertNull($this->context->getRequestedExclusionPolicy());
        self::assertTrue($this->context->has('requested_exclusion_policy'));
        self::assertNull($this->context->get('requested_exclusion_policy'));

        $this->context->setRequestedExclusionPolicy(ConfigUtil::EXCLUSION_POLICY_NONE);
        self::assertSame(ConfigUtil::EXCLUSION_POLICY_NONE, $this->context->getRequestedExclusionPolicy());
        self::assertTrue($this->context->has('requested_exclusion_policy'));
        self::assertSame(ConfigUtil::EXCLUSION_POLICY_NONE, $this->context->get('requested_exclusion_policy'));

        $this->context->setRequestedExclusionPolicy(ConfigUtil::EXCLUSION_POLICY_ALL);
        self::assertSame(ConfigUtil::EXCLUSION_POLICY_ALL, $this->context->getRequestedExclusionPolicy());
        self::assertTrue($this->context->has('requested_exclusion_policy'));
        self::assertSame(ConfigUtil::EXCLUSION_POLICY_ALL, $this->context->get('requested_exclusion_policy'));

        $this->context->setRequestedExclusionPolicy('');
        self::assertNull($this->context->getRequestedExclusionPolicy());
        self::assertTrue($this->context->has('requested_exclusion_policy'));
        self::assertNull($this->context->get('requested_exclusion_policy'));

        $this->context->setRequestedExclusionPolicy(null);
        self::assertNull($this->context->getRequestedExclusionPolicy());
        self::assertTrue($this->context->has('requested_exclusion_policy'));
        self::assertNull($this->context->get('requested_exclusion_policy'));
    }

    public function testExplicitlyConfiguredFieldNames(): void
    {
        self::assertSame([], $this->context->getExplicitlyConfiguredFieldNames());

        $this->context->setExplicitlyConfiguredFieldNames(['field1', 'field2']);
        self::assertSame(['field1', 'field2'], $this->context->getExplicitlyConfiguredFieldNames());

        $this->context->setExplicitlyConfiguredFieldNames([]);
        self::assertSame([], $this->context->getExplicitlyConfiguredFieldNames());
    }

    public function testExtras(): void
    {
        self::assertSame([], $this->context->getExtras());
        self::assertSame([], $this->context->get('extra'));
        self::assertFalse($this->context->hasExtra('test'));
        self::assertFalse($this->context->has('test'));

        $extras = [
            new TestConfigExtra('test', ['test_attr' => true]),
            new TestConfigExtra('test1')
        ];
        $this->context->setExtras($extras);
        self::assertEquals($extras, $this->context->getExtras());
        self::assertSame(['test', 'test1'], $this->context->get('extra'));
        self::assertTrue($this->context->hasExtra('test'));
        self::assertTrue($this->context->has('test_attr'));
        self::assertTrue($this->context->hasExtra('test1'));

        $this->context->removeExtra('test');
        self::assertEquals([$extras[1]], $this->context->getExtras());
        self::assertSame(['test1'], $this->context->get('extra'));
        self::assertFalse($this->context->hasExtra('test'));
        self::assertTrue($this->context->hasExtra('test1'));

        $this->context->setExtras([]);
        self::assertSame([], $this->context->getExtras());
        self::assertFalse($this->context->hasExtra('test'));
        self::assertSame([], $this->context->get('extra'));
    }

    public function testSetExtra(): void
    {
        self::assertSame([], $this->context->getExtras());
        self::assertSame([], $this->context->get('extra'));
        self::assertFalse($this->context->hasExtra('test'));
        self::assertFalse($this->context->has('test'));

        $extra = new TestConfigExtra('test', ['test_attr' => 'val1']);
        $this->context->setExtra($extra);
        self::assertSame([$extra], $this->context->getExtras());
        self::assertSame(['test'], $this->context->get('extra'));
        self::assertTrue($this->context->hasExtra('test'));
        self::assertTrue($this->context->has('test_attr'));
        self::assertEquals('val1', $this->context->get('test_attr'));

        $extra = new TestConfigExtra('test', ['test_attr' => 'val2']);
        $this->context->setExtra($extra);
        self::assertSame([$extra], $this->context->getExtras());
        self::assertSame(['test'], $this->context->get('extra'));
        self::assertTrue($this->context->hasExtra('test'));
        self::assertTrue($this->context->has('test_attr'));
        self::assertEquals('val2', $this->context->get('test_attr'));
    }

    public function testGetPropagableExtras(): void
    {
        self::assertSame([], $this->context->getPropagableExtras());

        $extras = [
            new TestConfigExtra('test'),
            new TestConfigSection('test_section')
        ];
        $this->context->setExtras($extras);
        self::assertEquals(
            [new TestConfigSection('test_section')],
            $this->context->getPropagableExtras()
        );

        $this->context->setExtras([]);
        self::assertSame([], $this->context->getPropagableExtras());
    }

    public function testConfigSections(): void
    {
        self::assertFalse($this->context->hasConfigSection('test'));
        self::assertNull($this->context->getConfigSection('test'));

        $sectionConfig = ['key' => 'val'];

        $this->context->setConfigSection('test', $sectionConfig);
        self::assertTrue($this->context->hasConfigSection('test'));
        self::assertEquals($sectionConfig, $this->context->getConfigSection('test'));

        $this->context->setConfigSection('test', null);
        self::assertTrue($this->context->hasConfigSection('test'));
    }

    public function testFilters(): void
    {
        self::assertFalse($this->context->hasFilters());
        self::assertNull($this->context->getFilters());

        $filters = new FiltersConfig();

        $this->context->setFilters($filters);
        self::assertTrue($this->context->hasFilters());
        self::assertEquals($filters, $this->context->getFilters());
        self::assertEquals($filters, $this->context->getConfigSection(FiltersConfigExtra::NAME));

        $this->context->setFilters(null);
        self::assertTrue($this->context->hasFilters());
    }

    public function testSorters(): void
    {
        self::assertFalse($this->context->hasSorters());
        self::assertNull($this->context->getSorters());

        $sorters = new SortersConfig();

        $this->context->setSorters($sorters);
        self::assertTrue($this->context->hasSorters());
        self::assertEquals($sorters, $this->context->getSorters());
        self::assertEquals($sorters, $this->context->getConfigSection(SortersConfigExtra::NAME));

        $this->context->setSorters(null);
        self::assertTrue($this->context->hasSorters());
    }
}
