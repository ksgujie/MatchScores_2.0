<?php namespace App\Http\Controllers;

use App\Modules\Excel\Excel;
use App\Modules\Excel\Template;
use App\Modules\MatchConfig\Item;
use App\User;
use Illuminate\Support\Facades\Redirect;

class ActionController extends Controller {

	public function run($do)
	{
		$this->$do();
		return Redirect::to('main/index')->with('message', date("Y-m-d H:i:s")."完成：$do \n耗时：". $this->runnedTime());
	}

	public function 导入报名数据()
	{
		$filename = matchConfig('路径.工作目录') . '/导入/报名数据导入.xls';
		$filename = gbk($filename);
		$objExcel = \PHPExcel_IOFactory::load( $filename );
		$objSheet = $objExcel->getActiveSheet();
		$arrData=$objSheet->toArray();
//		dump($arrData);die;
		//导入EXCEL文件中的字段
		$templateFields = array_map('trim', $arrData[0]);
		//users数据表中的字段
		$DbTableFields=[];
		//检测出数据库USERS表中真实的字段
		foreach ($templateFields as $_field) {
			if (\Schema::hasColumn('users', $_field )) {
				$DbTableFields[]=$_field;
			}
		}

//		$fields = $arrData[0];
		$insertCount = 0;
		for ($i = 1; $i < count($arrData); $i++) {
			$row = $arrData[$i];
			$arrInsert = [];
			for ($j = 0; $j < count($row); $j++) {
				$field = $templateFields[$j];
				if ( in_array($field, $DbTableFields) && strlen($row[$j]) ) {
					$arrInsert[$field] = trim($row[$j]);
				}
			}
			if ($arrInsert) {
				User::create($arrInsert);
				$insertCount++;
			}
		}

		return Redirect::to('/')->with('message', "成功，共导入{$insertCount}条数据！耗时：". $this->runnedTime());
	}

	public function 报名顺序()
	{
		$Users = User::orderBy('id')->get();
		$arrUsers = [];
		foreach ($Users as $user) {
			if (!isset($arrUsers[$user->参赛队])) {
				$arrUsers[$user->参赛队] = $user;
			}
		}

		foreach ($arrUsers as $user) {
			\DB::update("update users set 报名顺序=? where 参赛队=?", [$user->id, $user->参赛队]);
		}
	}

	/**
	 * 编排编号
	 */
	public function 编号()
	{
		$arrItems = array_keys(matchConfig('项目'));
		dump(matchConfig('项目'));
		for ($i = 0; $i < count($arrItems); $i++) {
			$item = $arrItems[$i];
			$arrGroups = matchConfig("项目.$item.组别");
			for ($j = 0; $j < count($arrGroups); $j++) {
				$group = $arrGroups[$j];
				$Users = User::where('项目', $item)
					->where('组别', $group)
					->orderBy('报名顺序')
					->orderBy('参赛队')
					->orderBy('id')
					->get();

				$n=1;
				foreach ($Users as $user) {
					$编号 = matchConfig("项目.$item.表名") . $this->lerrers[$j] . sprintf("%03d", $n);

					$user->编号 = $编号;
					$a=$user->save();

					$n++;
				}//foreach
			}//for j
		}//for i
	}

	public function 生成裁判用表()
	{
		$objTemplateExcel = Template::生成裁判用表模板(gbk(matchConfig('路径.工作目录').'/模板/裁判用表模板.xls'));
		$arrItems = matchConfig('裁判用表');
//		dd($arrItems);
		$objExcel = new Excel();
		foreach ($arrItems as $itemName => $item) {
//			dd($item);////////////////
			$data = User::where('项目', $itemName)->orderBy('编号')->get();
			$config = [
//				'objExcel'=>$objTemplateExcel,
				'templateFile'=>gbk(matchConfig('路径.工作目录').'/模板/裁判用表模板.xls'),
				'sheetName'=>$item['表名'],
				'firstDataRowNum'=>$item['首条数据行号'],
				'data'=>$data,
			];
//			dd($config);
			$objExcel->setConfig($config);
			$objExcel->make();

			//设置分页
			$objExcel->insertPageBreak('分组');
			//页眉、页脚
			$objExcel->objSheet->getHeaderFooter()->setOddHeader('&C&"黑体,常规"&16 '. matchConfig('全局.比赛名称') . "\n&\"宋体,常规\"&14（{$itemName}）" );
			$objExcel->objSheet->getHeaderFooter()->setOddFooter( '&L&P/&N页&R裁判员签名_______________ 项目裁判长签名_______________');
			//面边距
			$objExcel->objSheet->getPageMargins()->setHeader(0.3);
			$objExcel->objSheet->getPageMargins()->setTop(0.85);
			$objExcel->objSheet->getPageMargins()->setFooter(0.2);
			$objExcel->objSheet->getPageMargins()->setBottom(0.5);
			$objExcel->objSheet->getPageMargins()->setLeft(0.2);
			$objExcel->objSheet->getPageMargins()->setRight(0.2);
		}
		//缩小打印到一页
		if (matchConfig("裁判用表.$itemName.缩至一页")=='是') {
			$objExcel->printInOnePage();
		}
		//保存
		$objExcel->save(gbk(matchConfig('路径.工作目录').'/裁判用表.xls'));
	}

	public function 自定义导入()
	{
		$filename = matchConfig('路径.工作目录') . '/导入/自定义导入.xls';
		$filename = gbk($filename);
		$objExcel = \PHPExcel_IOFactory::load( $filename );
		$objSheet = $objExcel->getSheetByName('导入');
		if (!$objSheet) {
			exit("错误：未找到名为“导入”的表");
		}

		$arrData=$objSheet->toArray();
		//导入值的字段名
		$valueField = trim($arrData[0][1]);
		if (!\Schema::hasColumn('users', $valueField )) {
			exit("$valueField 数据库中没有此字段");
		}

		for ($i = 1; $i < count($arrData); $i++) {
			$value = trim($arrData[$i][1]);
			if (strlen($value)) {
				$user = User::where('编号', $arrData[$i][0])->firstOrFail();
				$user->分组 = $value;
				$user->save();
			}
		}
	}


}