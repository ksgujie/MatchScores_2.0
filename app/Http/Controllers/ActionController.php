<?php namespace App\Http\Controllers;

use App\User;
use Illuminate\Support\Facades\Redirect;

class ActionController extends Controller {

	public function run($do)
	{
		$this->$do();
		return Redirect::to('/')->with('message', date("Y-m-d H:i:s")."完成：$do \n耗时：". $this->runnedTime());
	}

	public function 导入报名数据()
	{
		$filename = mycfg('路径.工作目录') . '/导入/报名数据导入.xls';
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
		$arrItems = array_keys(mycfg('项目'));
		dump(mycfg('项目'));
		for ($i = 0; $i < count($arrItems); $i++) {
			$item = $arrItems[$i];
			$arrGroups = mycfg("项目.$item.组别");
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
					$编号 = mycfg("项目.$item.表名") . $this->lerrers[$j] . sprintf("%03d", $n);

					$user->编号 = $编号;
					$a=$user->save();

					$n++;
				}//foreach
			}//for j
		}//for i
	}

	public function 自定义导入()
	{
		$filename = mycfg('路径.工作目录') . '/导入/自定义导入.xls';
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