<?php
declare(strict_types=1);

namespace Oro\Component\Layout\Loader\Generator;

use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use Oro\Component\Layout\Loader\Visitor\VisitorInterface;
use Oro\Component\PhpUtils\ClassGenerator;

/**
 * Base class for generators of layout updates.
 */
abstract class AbstractLayoutUpdateGenerator implements LayoutUpdateGeneratorInterface
{
    private ?VisitorCollection $visitorCollection = null;

    public function generate(
        string $className,
        GeneratorData $data,
        ?VisitorCollection $visitorCollection = null
    ): string {
        $this->visitorCollection = $visitorCollection ?? new VisitorCollection();

        $this->prepare($data, $this->visitorCollection);
        $this->validate($data);

        $class = new ClassGenerator($className);
        $visitContext = new VisitContext($class);

        if ($data->getFilename()) {
            $class->addComment('Filename: '.$data->getFilename());
        }

        $class->addImplement(LayoutUpdateInterface::class);

        $method = $class->addMethod(LayoutUpdateGeneratorInterface::UPDATE_METHOD_NAME);

        $method->addParameter(LayoutUpdateGeneratorInterface::PARAM_LAYOUT_MANIPULATOR)
            ->setType(LayoutManipulatorInterface::class);

        $method->addParameter(LayoutUpdateGeneratorInterface::PARAM_LAYOUT_ITEM)
            ->setType(LayoutItemInterface::class);

        /** @var VisitorInterface $condition */
        foreach ($this->visitorCollection as $condition) {
            $condition->startVisit($visitContext);
        }

        $visitContext->appendToUpdateMethodBody($this->doGenerateBody($data));

        /** @var VisitorInterface $condition */
        foreach ($this->visitorCollection as $condition) {
            $condition->endVisit($visitContext);
        }

        $method->setBody($visitContext->getUpdateMethodBody());

        return "<?php\n\n" . $class->print();
    }

    public function getVisitorCollection(): ?VisitorCollection
    {
        return $this->visitorCollection;
    }

    /**
     * Performs code generation itself based on source data given
     */
    abstract protected function doGenerateBody(GeneratorData $data): string;

    /**
     * Do preparation of data and visitor collection based on resource data.
     * Empty implementation, could be overridden in descendants.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function prepare(GeneratorData $data, VisitorCollection $visitorCollection): void
    {
    }

    /**
     * Validates given resource data. Should throw exception if error found.
     * Empty implementation, could be overridden in descendants.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function validate(GeneratorData $data): void
    {
    }
}
