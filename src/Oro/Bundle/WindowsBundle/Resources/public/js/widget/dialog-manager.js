define(function(require) {
    'use strict';
    function DialogManager() {
        this.dialogs = [];
    }
    DialogManager.prototype = {
        dialogs: null,
        POSITION_SHIFT: 36,
        add: function(dialog) {
            this.dialogs.push(dialog);
        },
        remove: function(dialog) {
            const index = this.dialogs.indexOf(dialog);
            if (index === -1) {
                throw new Error('Could not remove unexisting dialog');
            }
            this.dialogs.splice(index, 1);
        },
        getDialogPositionList: function(exclude) {
            const positions = [];
            for (let i = 0; i < this.dialogs.length; i++) {
                const currentDialogWidget = this.dialogs[i];
                if (currentDialogWidget.widget && currentDialogWidget !== exclude) {
                    const dialogEl = currentDialogWidget.widget[0];
                    positions.push({
                        dialog: currentDialogWidget,
                        rect: dialogEl.getBoundingClientRect()
                    });
                }
            }
            return positions;
        },
        updateIncrementalPosition: function(dialogWidget) {
            dialogWidget.setPosition(this.preparePosition(0, 0));
            const positions = this.getDialogPositionList(dialogWidget);
            const baseRect = dialogWidget.widget[0].getBoundingClientRect();
            const basePosition = {
                top: baseRect.top,
                left: baseRect.left,
                width: baseRect.width,
                height: baseRect.height
            };
            const initialTop = basePosition.top;
            const initialLeft = basePosition.left;
            let exit = false;
            let i;
            while (exit !== true) {
                exit = true;
                for (i = 0; i < positions.length; i++) {
                    const position = positions[i];
                    if (this.getRectSimilarity(basePosition, position.rect) < this.POSITION_SHIFT) {
                        basePosition.top += this.POSITION_SHIFT;
                        basePosition.left += this.POSITION_SHIFT;
                        exit = false;
                        break;
                    }
                }
            }
            dialogWidget.setPosition(this.preparePosition(0, 0),
                basePosition.top - initialTop,
                basePosition.left - initialLeft);
        },

        preparePosition: function(offsetLeft, offsetTop) {
            return {
                my: 'center',
                at: 'center+' + offsetLeft + ' center+' + offsetTop,
                of: '#container'
            };
        },

        getRectSimilarity: function(aRect, bRect) {
            return Math.abs(aRect.top - bRect.top) +
                Math.abs(aRect.left - bRect.left);
        }
    };
    return DialogManager;
});
