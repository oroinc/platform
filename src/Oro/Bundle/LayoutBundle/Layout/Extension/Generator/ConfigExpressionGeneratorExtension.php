<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension\Generator;

use Oro\Component\ConfigExpression\AssemblerInterface;
use Oro\Component\Layout\Exception\SyntaxException;
use Oro\Component\Layout\Loader\Generator\ConfigLayoutUpdateGeneratorExtensionInterface;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;

class ConfigExpressionGeneratorExtension implements ConfigLayoutUpdateGeneratorExtensionInterface
{
    const NODE_CONDITIONS = 'conditions';

    /** @var AssemblerInterface */
    protected $expressionAssembler;

    /**
     * @param AssemblerInterface $expressionAssembler
     */
    public function __construct(AssemblerInterface $expressionAssembler)
    {
        $this->expressionAssembler = $expressionAssembler;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(GeneratorData $data, VisitorCollection $visitorCollection)
    {
        $source = $data->getSource();
        if (is_array($source) && !empty($source[self::NODE_CONDITIONS])) {
            try {
                $expr = $this->expressionAssembler->assemble($source[self::NODE_CONDITIONS]);
                if ($expr) {
                    $visitorCollection->append(new ConfigExpressionConditionVisitor($expr));
                }
            } catch (\Exception $e) {
                throw new SyntaxException(
                    'invalid conditions. ' . $e->getMessage(),
                    $source[self::NODE_CONDITIONS],
                    self::NODE_CONDITIONS
                );
            }
        }
    }
}
