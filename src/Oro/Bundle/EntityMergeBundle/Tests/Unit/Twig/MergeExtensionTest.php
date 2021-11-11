<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Oro\Bundle\EntityMergeBundle\Twig\MergeExtension;
use Oro\Bundle\EntityMergeBundle\Twig\MergeRenderer;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

class MergeExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var AccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $accessor;

    /** @var MergeRenderer|\PHPUnit\Framework\MockObject\MockObject */
    private $renderer;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var MergeExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->accessor = $this->createMock(AccessorInterface::class);
        $this->renderer = $this->createMock(MergeRenderer::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_entity_merge.accessor', $this->accessor)
            ->add('oro_entity_merge.twig.renderer', $this->renderer)
            ->add(TranslatorInterface::class, $this->translator)
            ->getContainer($this);

        $this->extension = new MergeExtension($container);
    }

    public function testSortMergeFields()
    {
        $foo = $this->createFormView(['name' => 'foo', 'label' => 'Foo']);
        $bar = $this->createFormView(['name' => 'bar', 'label' => 'Bar']);
        $baz = $this->createFormView(['name' => 'baz']);
        $actualFields = [$foo, $baz, $bar];
        $expectedFields = [$bar, $baz, $foo];

        $this->translator->expects($this->atLeastOnce())
            ->method('trans')
            ->willReturnArgument(0);

        $this->assertEquals(
            $expectedFields,
            self::callTwigFilter($this->extension, 'oro_entity_merge_sort_fields', [$actualFields])
        );
    }

    private function createFormView(array $vars): FormView
    {
        $result = $this->createMock(FormView::class);
        $result->vars = $vars;

        return $result;
    }
}
