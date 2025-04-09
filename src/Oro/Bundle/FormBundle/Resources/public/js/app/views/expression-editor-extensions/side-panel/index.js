import ButtonsCollectionView from './buttons-collection-view';
import defaultOperations from './default-operations';

export default function(operationButtons = [], util, codeMirror) {
    const operations = [
        ...defaultOperations,
        ...operationButtons
    ].reduce((operations, {handler, name, ...props}) => {
        const operation = {
            ...props,
            name,
            codeMirror
        };

        if (handler) {
            operation.handler = handler.bind(operation, codeMirror);
        }

        const foundDefaultIndex = operations.findIndex(({name: dName}) => dName === name);
        if (foundDefaultIndex !== -1) {
            operations.splice(foundDefaultIndex, 1, {
                ...operations[foundDefaultIndex],
                ...operation
            });
        } else {
            operations.push(operation);
        }

        return operations;
    }, []);

    const buttonsCollectionView = new ButtonsCollectionView({
        operationButtons: operations,
        allowedOperations: util.options.allowedOperations
    });

    return {
        top: true,
        dom: buttonsCollectionView.el,
        destroy() {
            buttonsCollectionView.dispose();
        }
    };
};
