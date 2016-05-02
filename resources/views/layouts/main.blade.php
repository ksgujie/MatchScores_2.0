<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ Session::get('action') }} 计分 V2 </title>
    <link href="{{ asset('/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <script href="{{ asset('/js/jquery.min.css') }}"></script>
    <script href="{{ asset('/js/bootstrap.min.css') }}"></script>
</head>
<body>

<table border="0">
	<tr>
		<td style="width: 200px">
			<h1>计分 v2</h1>
		</td>
		<td>
			{{ str_ireplace('.xlsx', '', Cache::get('配置文件','')) }} {{ Html::link('main/select', '更改') }}
		</td>
	</tr>
</table>
		
<!-- @include('_message') -->
	
@yield('content')

@if (Session::has('action'))
    <embed src="{!! asset('notice.wav') !!}" autostart=true width="0" heitht="0"></embed>
@endif
</body>
</html>
