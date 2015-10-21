<script>
    $(function() {
        $("#sortable").sortable({
            update: function(event, ui) {
                var item = ui.item, position = (item.prev('li').length) ? 'after' : 'before';

                $.post('<?=$route?>', {images:{
                    sortable: {
                        id: item.find('img').attr('data-id'), position: position, element: (item.prev('li').length) ? item.prev('li').find('img').attr('data-id') : item.next('li').find('img').attr('data-id')
                    }
                }});
            }
        }).disableSelection();
    });
</script>

<script type="text/javascript">
    function deleteImage(image) {
        $.post('<?=$route?>', {images:{
            delete: {
                id: image.data('id')
            }
        }}, function(response) {
\
        })
    }

    function setAsMain(image) {
        $.post('<?=$route?>', {images:{
            set_main: {
                id: image.data('id')
            }
        }}, function(response) {

        })
    }
</script>