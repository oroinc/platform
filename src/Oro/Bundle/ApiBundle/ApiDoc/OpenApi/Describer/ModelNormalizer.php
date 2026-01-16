<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer;

use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\FieldDescriptionUtil;

/**
 * Provides model normalization logic applicable to all API types.
 */
class ModelNormalizer implements ModelNormalizerInterface
{
    private const REQUIRED_NOTE = '<p><strong>The required field.</strong></p>';
    private const READ_ONLY_NOTE = '<p><strong>The read-only field. A passed value will be ignored.</strong></p>';

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function normalizeModel(array $model, string $action, bool $isResponseModel): array
    {
        foreach ($model as $name => $item) {
            $readonly = $item['readonly'] ?? null;
            $description = $item['description'] ?? null;
            if ($description) {
                if (str_contains($description, self::REQUIRED_NOTE)) {
                    $description = str_replace(self::REQUIRED_NOTE, '', $description);
                    $item['required'] = true;
                }
                if ($isResponseModel) {
                    if (str_contains($description, self::READ_ONLY_NOTE)) {
                        $description = str_replace(self::READ_ONLY_NOTE, '', $description);
                    }
                } elseif ($readonly && !str_contains($description, self::READ_ONLY_NOTE)) {
                    $description = FieldDescriptionUtil::addReadOnlyFieldNote($description);
                    $readonly = true;
                    $item['readonly'] = true;
                }
                $item['description'] = FieldDescriptionUtil::trimDescription($description);
                $model[$name] = $item;
            }
            if (false === $readonly || (null !== $readonly && $isResponseModel)) {
                unset($item['readonly']);
                $model[$name] = $item;
            }
        }

        return $model;
    }
}
