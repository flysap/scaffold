<style>
    #sortable { list-style-type: none; margin: 0; padding: 0; width: 450px; }
    #sortable li { margin: 3px 3px 3px 0; padding: 1px; float: left; width: 100px; height: 90px; font-size: 4em; text-align: center; }
</style>
<script>
    $(function() {
        $("#sortable").sortable({
            update: function(event, ui) {
                var element = ui.item;
                var prev = ui.item.prev();

                console.log([event, ui]);
                $.post('', {id: image.data('id')}, function(response) {

                })
            }
        });
        $("#sortable").disableSelection();
    });
</script>

<script type="text/javascript">
    function deleteImage(image) {
        $.post('', {id: image.data('id')}, function(response) {

        })
    }

    function setAsMain(image) {
        $.post('', {id: image.data('id')}, function(response) {

        })
    }
</script>