@extends('layouts.main')

@section('content')

<table border="0">
    <tr>
        <td>

            <ol>
                <li> {!! Html::link('action/导入报名数据', '导入[报名数据]') !!}</li>
                <li> {!! Html::link('action/报名顺序', '编排[报名顺序]') !!} </li>
                <li> {!! Html::link('action/编号', '编排[编号]') !!} </li>
                <li> {!! Html::link('action/分组', '编排[分组]') !!} </li>
                <li> {!! Html::link('action/生成检录用表', '生成检录用表') !!} </li>
                <li> {!! Html::link('action/生成裁判用表', '生成裁判用表') !!} </li>
                <li> {!! Html::link('action/生成分组情况表', '生成分组情况表') !!} </li>
                <li> {!! Html::link('action/生成成绩录入表', '生成成绩录入表') !!} </li>
                <li> {!! Html::link('action/成绩导入', '成绩导入') !!} </li>
                <li> {!! Html::link('score/计算成绩', '计算成绩') !!} </li>
                <li> {!! Html::link('score/优秀辅导员', '计算优秀辅导员') !!} </li>
                <li> {!! Html::link('score/生成获奖名单', '生成获奖名单（打印）') !!} </li>
                <li> {!! Html::link('score/生成综合团体成绩表', '生成综合团体成绩表（打印）') !!} </li>
                <li> {!! Html::link('score/生成优秀辅导员表', '生成优秀辅导员表（打印）') !!} </li>
                <li>生成综合团体成绩表（用于打印奖状）</li>
                <li>生成优秀辅导员表（用于打印奖状）</li>
                <li> {!! Html::link('score/生成成绩册', '生成成绩册') !!} </li>

            </ol>
        </td>
        <td align="center" valign="top" width="300">

            @if (Session::has('danger'))
                <div class="alert alert-danger" style="margin:10px 15px 0px 15px" >
                    <h4>{{ Session::get('danger') }}</h4>
                </div>

            @endif

            @if (session('message'))

                <div class="alert alert-warning" style="color:blue" >
                    <h4>{{ session('message') }}</h4>
                </div>

            @endif
        </td>
    </tr>
    <tr>
        <td>
            <ol>
                <li> {!! Html::link('action/更改姓名', '导入[更改姓名]') !!} </li>
                <li> {!! Html::link('action/添加名单', '导入[添加名单]') !!} </li>
                <li> {!! Html::link('action/自定义导入', '导入[自定义数值]') !!} </li>
            </ol>
        </td>
        <td>

        </td>
    </tr>
</table>

<embed src="{!! asset('notice.wav') !!}" autostart=true width="0" heitht="0"></embed>

@stop