var mergeView = {

    entitySelectAllHandler: function(){
        var entityId = $(this).data('entity-key');
        $('.entity-merge-field-choice[value="'+entityId+'"]').click();
    },
    entityValueSelectHandler: function(){
        var fieldName = $(this).attr('name');
        var entityKey = $(this).val();
        $('.merge-entity-representative[data-entity-field-name="'+fieldName+'"]').each(function(index, item){
             var $this = $(item);
            if($this.data('entity-key') != entityKey){
                $this.addClass('entity-merge-not-selected');
            }else{
                $this.removeClass('entity-merge-not-selected');
            }
        });
    },
    resetViewState:function(){
        $('input[type="radio"]:checked').click();
    },
    init:function(){
        $('.entity-merge-select-all').click(mergeView.entitySelectAllHandler);
        $('.entity-merge-field-choice').click(mergeView.entityValueSelectHandler);
        mergeView.resetViewState();
    }
};