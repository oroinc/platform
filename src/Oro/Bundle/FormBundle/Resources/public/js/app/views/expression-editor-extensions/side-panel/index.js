import ButtonsCollectionView from './buttons-collection-view';
import defaultOperations from './default-operations';

export default function(operationButtons = [], codeMirror) {
    const operations = [...defaultOperations];

    operationButtons.forEach(operation => {
        const definedOperation = operations.find(item => item.name === operation.name);

        if (definedOperation) {
            Object.assign(definedOperation, operation);
        } else {
            operations.push(operation);
        }
    });

    operations.forEach(operation => {
        if (operation.handler) {
            operation.handler = operation.handler.bind(operation, codeMirror);
        }
    });

    const buttonsCollectionView = new ButtonsCollectionView({operationButtons: operations});

    return {
        top: true,
        dom: buttonsCollectionView.el,
        destroy() {
            buttonsCollectionView.dispose();
        }
    };
};
