@if( $element->isAllowed() )
    @if(view()->exists('scaffold::scaffold.elements.' . $element->getAttribute('type')))
        @include('scaffold::scaffold.elements.' . $element->getAttribute('type'))
    @else
        <div class="form-group">
            <?php $label = $element->getAttribute('label') ?: $element->getAttribute('name'); ?>
            <label>{{ucfirst($label)}}</label>
            {!!Flysap\FormBuilder\render_element($element, $form, ['class' => 'form-control'])!!}
        </div>
    @endif
@endif