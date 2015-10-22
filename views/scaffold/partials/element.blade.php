@if( $element->isAllowed() )
    @if(view()->exists('scaffold::scaffold.elements.' . $element->getAttribute('type')))
        @include('scaffold::scaffold.elements.' . $element->getAttribute('type'))
    @else
        <div class="form-group">
            {!!Parfumix\FormBuilder\render_element($element, $form, array_merge(['before' => 'a', 'after' => 'b', 'class' => 'form-control'], $element->getAttributes()))!!}
        </div>
    @endif
@endif
