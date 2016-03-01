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
            var index = this.dialogs.indexOf(dialog);
            if (index === -1) {
                throw new Error('Could not remove unexisting dialog');
            }
            this.dialogs.splice(index, 1);
        },
        getDialogPositionList: function(exclude) {
            var positions = [];
            for (var i = 0; i < this.dialogs.length; i++) {
                var currentDialogWidget = this.dialogs[i];
                if (currentDialogWidget.widget && currentDialogWidget !== exclude) {
                    var dialogEl = currentDialogWidget.widget[0];
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
            var positions = this.getDialogPositionList(dialogWidget);
            var baseRect = dialogWidget.widget[0].getBoundingClientRect();
            var basePosition = {
                top: baseRect.top,
                left: baseRect.left,
                width: baseRect.width,
                height: baseRect.height
            };
            var initialTop = basePosition.top;
            var initialLeft = basePosition.left;
            var exit = false;
            var i;
            while (exit !== true) {
                exit = true;
                for (i = 0; i < positions.length; i++) {
                    var position = positions[i];
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
