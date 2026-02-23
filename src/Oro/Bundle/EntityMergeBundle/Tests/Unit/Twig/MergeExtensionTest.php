<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Oro\Bundle\EntityMergeBundle\Twig\MergeExtension;
use Oro\Bundle\EntityMergeBundle\Twig\MergeRenderer;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

class MergeExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private AccessorInterface&MockObject $accessor;
    private MergeRenderer&MockObject $renderer;
    private TranslatorInterface&MockObject $translator;
    private MergeExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->accessor = $this->createMock(AccessorInterface::class);
        $this->renderer = $this->createMock(MergeRenderer::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_entity_merge.accessor', $this->accessor)
            ->add(MergeRenderer::class, $this->renderer)
            ->add(TranslatorInterface::class, $this->translator)
            ->getContainer($this);

        $this->extension = new MergeExtension($container);
    }

    public function testSortMergeFields(): void
    {
        $foo = $this->createFormView(['name' => 'foo', 'label' => 'Foo']);
        $bar = $this->createFormView(['name' => 'bar', 'label' => 'Bar']);
        $baz = $this->createFormView(['name' => 'baz']);
        $actualFields = [$foo, $baz, $bar];
        $expectedFields = [$bar, $baz, $foo];

        $this->translator->expects(self::atLeastOnce())
            ->method('trans')
            ->willReturnArgument(0);

        self::assertEquals(
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
