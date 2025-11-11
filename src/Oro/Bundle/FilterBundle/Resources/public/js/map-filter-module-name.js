const moduleNameTemplate = 'oro/filter/{{type}}-filter';
const types = {
    'string': 'choice',
    'choice': 'select',
    'single_choice': 'select',
    'multichoice': 'multiselect',
    'boolean': 'boolean',
    'duplicate': 'select',
    'dictionary': 'dictionary'
};

export default function(type) {
    return moduleNameTemplate.replace('{{type}}', types[type] || type);
};
