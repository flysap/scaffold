@extends('themes::layouts.default')

@section('content')
    <section class="content-header">
        <h1>
            Page Header
            <small>Optional description</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
            <li class="active">Here</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content edit-block">

        {!!Parfumix\FormBuilder\open_form($form)!!}
            <div class="row">
            <div class="col-md-12">
                @if( Parfumix\FormBuilder\has_groups($form) )
                    <!-- Custom Tabs -->
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs">
                            <?php $counter = 0; ?>
                            @foreach($form->getGroups() as $key => $group)
                                <?php $counter++; ?>
                                <li class="{{$counter == 1 ? 'active' : ''}}"><a href="#tab_{{$counter}}" data-toggle="tab">{{ucfirst($key)}}</a></li>
                            @endforeach
                        </ul>

                        <div class="tab-content">
                            <?php $counter = 0; ?>
                            @foreach($form->getGroups() as $key => $group)
                                <?php $counter++; ?>
                                <div class="tab-pane {{$counter == 1 ? 'active' : ''}}" id="tab_{{$counter}}">
                                    <?php $elements = $form->getGroup($key)->getElements(); ?>

                                    @foreach($elements as $element)
                                        @include('scaffold::scaffold.partials.element')
                                    @endforeach

                                </div><!-- /.tab-pane -->
                            @endforeach

                        </div><!-- /.tab-content -->
                    </div><!-- nav-tabs-custom -->
                @else
                    <div class="box">
                        <div class="box-body">
                            <?php $elements = $form->getElements(); ?>

                            @foreach($elements as $element)
                                @include('scaffold::scaffold.partials.element')
                            @endforeach

                        </div><!-- /.box-body -->
                    </div><!-- /.box -->
                @endif

            </div><!-- /.col -->
        </div> <!-- /.row -->
        {!!Parfumix\FormBuilder\render_button(['value' => 'Submit', 'type' => 'submit'])!!}
        {!!Parfumix\FormBuilder\close_form($form)!!}

    </section><!-- /.content -->
@endsection
