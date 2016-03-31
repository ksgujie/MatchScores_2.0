<?php namespace App\Http\Controllers;

use App\Modules\MatchConfig\Item;
use App\Modules\MatchConfig\Score;
use App\Modules\Score\Sort;
use App\Modules\Score\Calc;
use App\User;

class ScoreController extends Controller {

	public function run($do)
	{
		dd($do);
		$this->$do();
		return redirect('main/index')->with('message', date("Y-m-d H:i:s")."完成：$do \n耗时：". $this->runnedTime());
	}
//	public function 计算成绩()
//	{
//		$cf
//		$a1=\PHPExcel_IOFactory::load('g:/a1.xls');
//		$a2=\PHPExcel_IOFactory::load('g:/a2.xls');
//		$excel=new \PHPExcel();
//
//		$s1=clone $a1->getSheetByName('A1');
//		$s2=clone $a2->getSheetByName('A2');
//		$excel->addSheet($s1);
//		$excel->addSheet($s2);
//		$excel->removeSheetByIndex(0);
//		$w=new \PHPExcel_Writer_Excel5($excel);
//		$w->save('g:/a.xls');
//
////		$this->_计算成绩('1/10遥控电动越野竞速赛');
//	}
	
	public function 计算成绩()
	{
		$项目名称=urldecode($_SERVER['QUERY_STRING']);

		$cfgItem	= new Item($项目名称);
		$cfgScore	= new Score($项目名称);
		
		$calc = new Calc();
		$calc->填充排序字段($项目名称);
		
		foreach ($cfgItem->组别 as $group) {
			$calc->单项排名($项目名称, $group, $cfgScore->排序方式);
			$calc->奖项($项目名称, $group, $cfgScore->获奖比例);
		}

		return redirect('main/index')->with('message', $项目名称 . ' 计算完成');
	}



	public function 生成成绩册()
	{
		$arrItems = SysConfig::items();
		$objExcel = new Excel();
		foreach ($arrItems as $item) {
			SysConfig::setItem($item);
			$arrGroups = SysConfig::itemGroups($item);
			$orderByGroup = "'" . join("','", $arrGroups) . "'";
			$users = User:: whereRaw(
				"项目=? order by  FIELD(组别, $orderByGroup), 排名, 编号 ",
				[$item]
			)->get();

			$config = [
				'templateFile' => SysConfig::template('成绩册'),
				'sheetName' => str_replace(',', '', SysConfig::item('表名')),
				'firstDataRowNum' => SysConfig::item('首条数据行号'),
				'data' => $this->处理成绩册数据($users),
			];
			$objExcel->setConfig($config);
			$objExcel->make();

			//页眉、页脚
			$objExcel->sheet->getHeaderFooter()->setOddHeader('&C&"黑体,常规"&16 ' . config('my.比赛名称') . "&\"宋体,常规\"&14 成绩册");
			$objExcel->sheet->getHeaderFooter()->setOddFooter('&C&P/&N页');

			//打印到一页
			$objExcel->printInOnePage();
		}//foreach items as item

		$objExcel->save(SysConfig::saveExcelDir() . utf8ToGbk("/成绩册.xlsx"));
	}//生成成绩册


	/**
	 * 产生一个供打印证书使用的获奖名单：个人、单项团体、综合团体
	 */
	public function 生成获奖名单()
	{
		$users = User::where('奖项','<>',"")
			->orderBy('单位')
			->orderBy('项目')
			->orderBy('组别')
			->orderBy('排名')
			->get();

		$config = [
			'templateFile' => SysConfig::template('获奖名单_打印'),
			'sheetName' => '个人',
			'firstDataRowNum' => 2,
			'data' => $users,
		];
		$objExcel = new Excel();
		$objExcel->setConfig($config);
		$objExcel->make();
		$objExcel->save(SysConfig::saveExcelDir() . utf8ToGbk("/获奖名单_打印.xlsx"));
	}

}