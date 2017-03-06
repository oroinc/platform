<?php

namespace Oro\Bundle\TranslationBundle\Datagrid\Extension\MassActions\Handlers;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Symfony\Component\Translation\TranslatorInterface;

class ResetTranslationsMassActionHandler implements MassActionHandlerInterface
{
    /**
     * @var TranslationManager
     */
    protected $translationManager;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslationManager $translationManager, TranslatorInterface $translator)
    {
        $this->translationManager = $translationManager;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $data = $args->getData();
        if (!array_key_exists('values', $data) || !array_key_exists('inset', $data)) {
            return new MassActionResponse(false, $this->translator->trans('oro.translation.action.reset.failure'));
        }

        if ($data['values'] === '' && $data['inset'] === '0') {
            $affectedCount = $this->translationManager->resetAllTranslations();
        } else {
            $ids = $this->extractTranslationIds($data['values']);
            $affectedCount = $this->translationManager->resetTranslations($ids);
        }
        $this->translationManager->flush();

        return new MassActionResponse(
            true,
            $this->translator->trans('oro.translation.action.reset.success'),
            ['count' => $affectedCount]
        );
    }

    /**
     * @param string $values
     * @return array
     */
    protected function extractTranslationIds($values)
    {
        $ids = [];
        foreach (explode(',', $values) as $id) {
            $id = (int)$id;
            if ($id) {
                $ids[] = $id;
            }
        }
        return $ids;
    }
}
