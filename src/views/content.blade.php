<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ Admin::title() }} @if($header) | {{ $header }}@endif</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    @if(!is_null($favicon = Admin::favicon()))
        <link rel="shortcut icon" href="{{$favicon}}">
    @endif

    {!! Admin::css() !!}

    <script src="{{ Admin::jQuery() }}"></script>
    {!! Admin::headerJs() !!}
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body class="hold-transition {{config('admin.skin')}} {{join(' ', config('admin.layout'))}}">
@if($alert = config('admin.top_alert'))
    <div style="text-align: center;padding: 5px;font-size: 12px;background-color: #ffffd5;color: #ff0000;">
        {!! $alert !!}
    </div>
@endif
<div class="wrapper">
    <!-- Main Header -->
    {!! $includeView('header', []) !!}
    {!! $includeView('aside', []) !!}
    {{--@include('header')--}}
    {{--@include('aside')--}}

    <div class="content-wrapper" id="pjax-container" style="margin-left: 0px">
        {!! Admin::style() !!}
        <div id="app">
            @if(!$hideHeader)
                <section class="content-header">
                    @if(!$hideDescription)
                        <h1>
                            {!! $header ?: trans('admin.title') !!}
                            <small>{!! $description ?: trans('admin.description') !!}</small>
                        </h1>
                    @endif
                <!-- breadcrumb start -->
                    @if($breadcrumb && !$hideBreadcrumb)
                        <ol class="breadcrumb" style="margin-right: 30px;">
                            <li><a href="{{admin_url('/')}}"><i class="fa fa-dashboard"></i>
                                    Home</a></li>
                            @foreach($breadcrumb as $item)
                                @if($loop->last)
                                    <li class="active">
                                        @if (\Illuminate\Support\Arr::has($item, 'icon'))
                                            <i class="fa fa-{{ $item['icon'] }}"></i>
                                        @endif
                                        {{ $item['text'] }}
                                    </li>
                                @else
                                    <li>
                                        @if (\Illuminate\Support\Arr::has($item, 'url'))
                                            <a href="{{ admin_url(\Illuminate\Support\Arr::get($item, 'url')) }}">
                                                @if (\Illuminate\Support\Arr::has($item, 'icon'))
                                                    <i class="fa fa-{{ $item['icon'] }}"></i>
                                                @endif
                                                {{ $item['text'] }}
                                            </a>
                                        @else
                                            @if (\Illuminate\Support\Arr::has($item, 'icon'))
                                                <i class="fa fa-{{ $item['icon'] }}"></i>
                                            @endif
                                            {{ $item['text'] }}
                                        @endif
                                    </li>
                                @endif
                            @endforeach
                        </ol>
                    @elseif(config('admin.enable_default_breadcrumb'))
                        <ol class="breadcrumb" style="margin-right: 30px;">
                            <li><a href="{{ admin_url('/') }}"><i class="fa fa-dashboard"></i> {{__('Home')}}</a></li>
                            @for($i = 2; $i <= count(Request::segments()); $i++)
                                <li>
                                    {{ucfirst(Request::segment($i))}}
                                </li>
                            @endfor
                        </ol>
                    @endif
                </section>
            @endif
            <section class="content" style="padding: 0px;">
                {{--@include('alerts')--}}
                {{--@include('exception')--}}
                {{--@include('toastr')--}}
                {!! $includeView('alerts', []) !!}
                {!! $includeView('exception', ['errors' => session()->get('errors')]) !!}
                {!! $includeView('toastr', []) !!}
                @if($_view_)
                    {!! $includeView($_view_['view'], $_view_['data']) !!}
                @else
                    {!! $_content_ !!}
                @endif
            </section>
        </div>
        {!! Admin::script() !!}
        {!! Admin::html() !!}
    </div>

    @if (!$hideFooter)
        {!! $includeView('footer') !!}
        {{--@include('footer')--}}
    @endif


</div>
<button id="totop" title="Go to top" style="display: none;"><i class="fa fa-chevron-up"></i></button>
<script>
    function LA() {
    }

    LA.token = "{{ csrf_token() }}";
    LA.user = @json($_user_);
</script>
{!! Admin::js() !!}
</body>
</html>

