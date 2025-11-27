const moduleNameTemplate = 'oro/{{type}}-widget';

export default function(type) {
    return moduleNameTemplate.replace('{{type}}', type);
};
