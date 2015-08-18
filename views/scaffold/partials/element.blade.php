<div class="form-group">
    <label>{{ucfirst($element->getAttribute('name'))}}</label>
    {!!Flysap\FormBuilder\render_element($element, $form, ['class' => 'form-control'])!!}
</div>