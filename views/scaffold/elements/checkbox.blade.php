<div class="checkbox">
    <label>
        <?php $label = $element->getAttribute('label') ?: $element->getAttribute('name'); ?>
        {!!Flysap\FormBuilder\render_element($element, $form)!!}
        {{$label}}
    </label>
</div>