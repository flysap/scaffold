@extends('themes::layouts.default')

@section('content')
    <section class="content-header">
        <h1>
            {{isset($title) ? $title : ''}}
            <small>{{isset($description) ? $description : ''}}</small>
        </h1>
        {{--<ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
            <li class="active">Here</li>
        </ol>--}}
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">

            @if($widgets)
                <div class="col-xs-12">
                    <div class="box-body table-responsive">
                        {!! Widget::render($widgets) !!}
                    </div><!-- /.box-body -->
                </div><!-- /.col -->
            @endif

            <div class="col-xs-12">
                <div class="box">
                    {!! Parfumix\TableManager\render_filter_form($table) !!}
                </div>
                <a class="btn btn-lg btn-flat btn-primary mb-10" href="{{ route('scaffold::create', ['eloquent_path' => $path]) }}">
                    <i class="fa fa-plus-circle"></i> {{_('New')}}
                </a>
                <div class="box">
                    <div class="box-header">
                        <div class="box-tools">
                            {!! $scopes->render(null, 'pagination pagination-sm no-margin pull-left') !!}
                        </div>
                    </div><!-- /.box-header -->
                    <div class="box-body table-responsive no-padding">
                        {!! $table->render() !!}
                        {!! Parfumix\TableManager\render_pagination($table, null, ['scope' => request('scope')]) !!}
                        {{_('Download')}} : {!! $exporters->render() !!}
                    </div><!-- /.box-body -->
                </div><!-- /.box -->

                
            </div><!-- /.col -->
        </div><!-- /.row -->
    </section><!-- /.content -->
@endsection