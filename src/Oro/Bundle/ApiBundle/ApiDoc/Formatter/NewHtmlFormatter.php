<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Formatter;

use Nelmio\ApiDocBundle\DataTypes as ApiDocDataTypes;

/**
 * The HTML formatter that is used for new REST API views, e.g. "rest_json_api" and "rest_plain".
 */
class NewHtmlFormatter extends HtmlFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function renderOne(array $data)
    {
        return $this->engine->render('NelmioApiDocBundle::resource.html.twig', array_merge(
            [
                'data'           => $this->reformatData($data),
                'displayContent' => true,
            ],
            $this->getGlobalVars()
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function render(array $collection)
    {
        return $this->engine->render('NelmioApiDocBundle::resources.html.twig', array_merge(
            [
                'resources' => $this->reformatDocData($collection),
            ],
            $this->getGlobalVars()
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewName($name, $data, $parentName = null)
    {
        if ($parentName) {
            return sprintf('%s[%s]', $parentName, $name);
        }

        return $name;
    }

    /**
     * {@inheritdoc}
     */
    protected function compressNestedParameters(array $data, $parentName = null, $ignoreNestedReadOnly = false)
    {
        $newParams = parent::compressNestedParameters($data, $parentName, $ignoreNestedReadOnly);
        foreach ($data as $name => $info) {
            if (isset($info['actualType']) && ApiDocDataTypes::COLLECTION === $info['actualType']) {
                $newName = $this->getNewName($name, $info, $parentName);
                $newParams[$newName]['dataType'] = 'array of ' . $info['dataType'];
            }
        }

        return $newParams;
    }

    /**
     * Re-formats input and output data for a set of resource sections.
     *
     * @param array $collection
     *
     * @return array
     */
    protected function reformatDocData(array $collection)
    {
        foreach ($collection as $resourceBlockName => $resourceGroupBlock) {
            foreach ($resourceGroupBlock as $resourceUrl => $resourceBlock) {
                foreach ($resourceBlock as $resourceId => $resource) {
                    $collection[$resourceBlockName][$resourceUrl][$resourceId] = $this->reformatData($resource);
                }
            }
        }

        return $collection;
    }

    /**
     * Re-formats input and output data for collected resource.
     *
     * @param array $data
     *
     * @return array
     */
    protected function reformatData(array $data)
    {
        // reformat parameters (input data)
        if (array_key_exists('parameters', $data)) {
            $data['documentation'] .= $this->engine->render(
                'OroApiBundle:ApiDoc:input.html.twig',
                ['data' => $data['parameters']]
            );
            unset($data['parameters']);
        }

        // reformat output
        if (array_key_exists('response', $data)) {
            $data['documentation'] .= $this->engine->render(
                'OroApiBundle:ApiDoc:response.html.twig',
                ['data' => $data['response']]
            );
            $data['parsedResponseMap'] = [];
        }

        return $data;
    }
}
