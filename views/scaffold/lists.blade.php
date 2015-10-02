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
    <section class="content">
        <div class="row">
            <div class="col-xs-9">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">Responsive Hover Table</h3>
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
            <div class="col-xs-3">
                <div class="box">
                    <a href="{{ route('scaffold::create', ['eloquent_path' => $model]) }}">
                        {{_('New')}}
                    </a>
                </div>
                <div class="box">
                {!! Parfumix\TableManager\render_filter_form($table) !!}
                </div>
            </div><!-- /.col -->
        </div><!-- /.row -->
    </section><!-- /.content -->
@endsection