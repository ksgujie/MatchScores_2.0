@extends('layouts.main')

@section('content')

{!! Form::open() !!}
{!! Form::select('file', $files, null, ['placeholder'=>'请选择比赛']) !!}
{!! Form::submit('初始化 / 进入') !!}
{!! Form::close() !!}


@stop