const moduleNameTemplate = 'oro/datagrid/action/{{type}}-action';

export default function(type) {
    return moduleNameTemplate.replace('{{type}}', type);
};
