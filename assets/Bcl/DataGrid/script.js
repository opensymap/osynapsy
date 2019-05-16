BclDataGrid = 
{
    init : function()
    {
        $('.bcl-datagrid').parent().on('click tap','.row',function(){
            if (!$(this).data('url-detail')) {
                return;
            }
            FormController.saveHistory();
            window.location = $(this).data('url-detail');            
        }).on('click','.bcl-datagrid-th-order-by',function(){
            if (!$(this).data('idx')) {
                return;
            }            
            var gridId = $(this).closest('.bcl-datagrid').attr('id');
            var orderByField = $('.BclPaginationOrderBy','#'+gridId);
            var orderByString = orderByField.val();
            var curColumnIdx = $(this).data('idx');
            if (orderByString.indexOf('[' + curColumnIdx +']') > -1){
                orderByString = orderByString.replace('[' + curColumnIdx + ']','[' + curColumnIdx + ' DESC]');                
            } else if (orderByString.indexOf('[' + curColumnIdx +' DESC]') > -1) {
                orderByString = orderByString.replace('[' + curColumnIdx + ' DESC]','');                               
            } else {
                orderByString += '[' + curColumnIdx + ']';                
            }
            $('.BclPaginationCurrentPage','#'+gridId).val(1);
            orderByField.val(orderByString);
            Osynapsy.refreshComponents([gridId]);
        });
    }
};

if (window.FormController){    
    FormController.register('init','BclDataGrid_Init',function(){
        BclDataGrid.init();
    });
}


