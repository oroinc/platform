const moduleNameTemplate = 'oro/datagrid/cell/{{type}}-cell';
const types = {
    'integer': 'number',
    'decimal': 'number',
    'percent': 'number',
    'currency': 'number',
    'array': 'string',
    'simple_array': 'string',
    'enum': 'string'
};

export default function(type) {
    return moduleNameTemplate.replace('{{type}}', types[type] || type);
};
