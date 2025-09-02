<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Twig;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariablesProvider;

/**
 * Provides entity variable definitions that could be used as email template attachments.
 */
class EmailTemplateAttachmentVariablesProvider
{
    /**
     * The maximum depth for traversing entity variables.
     * Default value is 1, which means that only root entity variables will be returned.
     * If you need to traverse deeper, you can set a higher value using `setMaxDepth` method.
     */
    private int $maxDepth = 1;

    public function __construct(private readonly VariablesProvider $variablesProvider)
    {
    }

    /**
     * Sets the maximum depth for traversing entity variables.
     */
    public function setMaxDepth(int $maxDepth): void
    {
        $this->maxDepth = $maxDepth;
    }

    /**
     * Returns a list of entity variable definitions that could be used as email template attachments.
     *
     * @param string $entityClass
     * @return array<string, array{label: string, type: string, related_entity_name: ?string}> An associative array
     *  where keys are variable names and values are their definitions. Example:
     *      [
     *         'entity.pdfFile' => [
     *              'label' => 'PDF File',
     *              'type' => 'ref-one',
     *              'related_entity_name' => File::class,
     *         ],
     *         // ...
     *         'entity.documents' => [
     *              'label' => 'Documents',
     *              'type' => 'multiFile',
     *              'related_entity_name' => FileItem::class,
     *         ],
     *         //...
     *      ]
     */
    public function getAttachmentVariables(string $entityClass): array
    {
        $entityVariableDefinitions = $this->variablesProvider->getEntityVariableDefinitions();
        if (empty($entityVariableDefinitions[$entityClass])) {
            return [];
        }

        return $this->doGetEntityVariables(['entity'], [], $entityVariableDefinitions, $entityClass);
    }

    /**
     * @codingStandardsIgnoreStart
     *
     * @param array<string> $parents
     *  Example:
     *      ['entity', 'owner']
     * @param array<string> $parentLabels
     *  Example:
     *     ['Owner']
     * @param array<string, array<string, array{label: string, type: string, related_entity_name: ?string}>> $entityVariableDefinitions
     *  Example:
     *      [
     *          User::class => [
     *              'entity.avatar' => [
     *                  'label' => 'Avatar',
     *                  'type' => 'ref-one',
     *                  'related_entity_name' => File::class,
     *              ],
     *              // ...
     *          ],
     *          // ...
     *      ]
     * @param string $entityClass
     *
     * @return array<string, array{label: string, type: string, related_entity_name: ?string}>
     *
     * @codingStandardsIgnoreEnd
     */
    private function doGetEntityVariables(
        array $parents,
        array $parentLabels,
        array $entityVariableDefinitions,
        string $entityClass
    ): array {
        if (count($parents) > $this->maxDepth) {
            return [];
        }

        $attachmentVariables = [];
        foreach ($entityVariableDefinitions[$entityClass] ?? [] as $variableName => $entityVariableDefinition) {
            $relatedEntityName = $entityVariableDefinition['related_entity_name'] ?? null;
            if (!isset($relatedEntityName)) {
                continue;
            }

            if ($relatedEntityName === File::class || $relatedEntityName === FileItem::class) {
                $entityVariableDefinition['label'] = implode(
                    ' / ',
                    array_merge($parentLabels, [$entityVariableDefinition['label']])
                );
                $attachmentVariables[0][implode(
                    '.',
                    array_merge($parents, [$variableName])
                )] = $entityVariableDefinition;
            } else {
                $attachmentVariables[] = $this->doGetEntityVariables(
                    array_merge($parents, [$variableName]),
                    $parentLabels + [$entityVariableDefinition['label']],
                    $entityVariableDefinitions,
                    $relatedEntityName
                );
            }
        }

        return array_merge(...$attachmentVariables);
    }
}
