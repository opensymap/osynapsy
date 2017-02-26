/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
BclAutocomplete = {
    init : function()
    {
        $('div.osy-autocomplete input[type=text]').keyup(function(event){
            switch (event.keyCode) {
                case 13 : //Enter
                    $('.row.selected','#search_content').trigger('click');
                    break;
                case 27 :
                    $('#search_content').remove();
                    break;
                case 38 : // up
                    if ($('#search_content').length > 0) {
                        $('#search_content').trigger('arrow-up');
                    } 
                    break;
                case 40 :
                    if ($('#search_content').length > 0) {
                        $('#search_content').trigger('arrow-down');
                    }
                    break;
                default:
                    if ($(this).val() == '') {
                        return;
                    }
                    var objId = $(this).attr('id');
                    var dat = $('form').serialize();
                        dat += '&ajax='+objId;                     
                    $.ajax({
                        type : 'post',
                        context : this,
                        data : dat,
                        success : function(rsp) {                            
                            var numRow = $('#' + $(this).attr('id') + '_list div.row',rsp).length;
                            if (numRow == 0) {
                                $('#search_content').remove();
                                return;
                            }
                            BclAutocomplete.openSearchContainer(this);
                            $('#search_content').html($('#' + $(this).attr('id') + '_list', rsp));
                        }
                    });
                    break;
            }
        }).attr('autocomplete','off');
        $(window).on('click',function(){
            $('#search_content').remove();
            //$('div.osy-textsearch-inline input[type=text]').val('');
        })
    },
    openSearchContainer : function(obj)
    {        
        if ($('#search_content').length > 0) {
            return;
        }
        $(obj).addClass('osy-autocomplete-unselected');
        $(obj).prev().val('');
        var pos = this.calcSearchContainerPosition($(obj).parent());
        var div = $('<div id="search_content" class="osy-autocomplete-listbox" style="position: absolute; top:'+(pos.top)+'px; left : '+pos.left+'px; width: '+pos.width+'px; max-height: '+pos.height+'px;"></div>');
        div.on('arrow-up',function(e) {
           if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            if ($('.row.selected',this).length == 0) {                
                $('.row:last',this).addClass('selected');
            } else if($('.row.selected',this).is(':first-child')){                
                $('.row.selected').removeClass('selected');
                $('.row:last',this).addClass('selected');
            } else {                
                $('.row.selected').removeClass('selected').prev().addClass('selected');
            }
        }).on('arrow-down',function(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            if ($('.row.selected',this).length == 0) {                
                $('.row:first',this).addClass('selected');
            } else if($('.row.selected',this).is(':last-child')){                
                $('.row.selected').removeClass('selected');
                $('.row:first',this).addClass('selected');
            } else {
                $('.row.selected').removeClass('selected').next().addClass('selected');
            }
        }).on('click','div.row',function(e){ 
            e.preventDefault();
            parentid = $(this).closest('#search_content').data('parent');            
            $('#'+parentid).removeClass('osy-autocomplete-unselected').nextAll('#__'.parentid).val($(this).data('value'));
            $('#'+parentid).val($(this).data('label'));
        }).data('parent',$(obj).attr('id'));
        $(document.body).append(div);
    },
    calcSearchContainerPosition : function(par)
    {
        var divPosition = {
            top   : 0,
            left  : 0,
            width : 0,
            height: 0
        }
        var parentWidth = $(par).width();
        var parentPosition = $(par).offset();
        //console.log(parentPosition);
        var windowWidth = $(window).width();
        var windowHeight = $(window).height();  
        parentPosition.right = parentPosition.left + $(par).width();
        parentPosition.bottom = parentPosition.top + $(par).outerHeight();
        
        divPosition.top = parentPosition.bottom;                                
        divPosition.height = Math.max(100,windowHeight - (parentPosition.bottom + 50));
        if (500 > (windowWidth - parentPosition.left)) {
            //console.log('angolo destro', parentWidth, parentPosition.left, windowWidth);
            //Posiziono il SearchContent partendo dall'angolo destro del componente
            divPosition.left = parentPosition.right - 500;
            divPosition.width = 500;
        } else {
            //console.log('angolo sinistro');
            //Posizione il SearchContent partendo dall'angolo sinistro del componente
            divPosition.left = parentPosition.left;
            divPosition.width = parentWidth > 500 ? parentWidth : windowWidth - (parentPosition.left + 50);
        }
        return divPosition;
    },
    closeSearchContainer : function(parent)
    {
        alert('chiudi');
    }
};

if (window.FormController) {
    FormController.register('init','BclAutocomplete',function(){ 
        BclAutocomplete.init(); 
    });
}


