export const createErrorValidationConfig = (node, message, props = {}) => {
    return {
        from: node.from,
        to: node.to,
        severity: 'error',
        message,
        ...props
    };
};
