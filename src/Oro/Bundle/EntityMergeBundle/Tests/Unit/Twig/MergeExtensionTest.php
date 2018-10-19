<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model;

use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Oro\Bundle\EntityMergeBundle\Twig\MergeExtension;
use Oro\Bundle\EntityMergeBundle\Twig\MergeRenderer;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\Translation\TranslatorInterface;

class MergeExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var MergeExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $accessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $renderer;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    protected function setUp()
    {
        $this->accessor = $this->createMock(AccessorInterface::class);
        $this->renderer = $this->getMockBuilder(MergeRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->createMock(TranslatorInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_entity_merge.accessor', $this->accessor)
            ->add('oro_entity_merge.twig.renderer', $this->renderer)
            ->add('translator', $this->translator)
            ->getContainer($this);

        $this->extension = new MergeExtension($container);
    }

    public function testSortMergeFields()
    {
        $foo = $this->createFormView(array('name' => 'foo', 'label' => 'Foo'));
        $bar = $this->createFormView(array('name' => 'bar', 'label' => 'Bar'));
        $baz = $this->createFormView(array('name' => 'baz'));
        $actualFields = array($foo, $baz, $bar);
        $expectedFields = array($bar, $baz, $foo);

        $this->translator->expects($this->atLeastOnce())
            ->method('trans')
            ->will($this->returnArgument(0));

        $this->assertEquals(
            $expectedFields,
            self::callTwigFilter($this->extension, 'oro_entity_merge_sort_fields', [$actualFields])
        );
    }

    protected function createFormView(array $vars)
    {
        $result = $this->createMock('Symfony\\Component\\Form\\FormView');
        $result->vars = $vars;
        return $result;
    }
}
