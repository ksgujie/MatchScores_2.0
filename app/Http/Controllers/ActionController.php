<?php namespace App\Http\Controllers;

use App\Modules\Excel\FillData;
use App\Modules\Excel\Template;
use App\Modules\MatchConfig\Item;
use App\Modules\Score\Show;
use App\User;
use Illuminate\Support\Facades\Session;

class ActionController extends Controller {

	//成绩备注。记录在成绩字段里的备注信息，用来判断获取该列成绩是否需要保留格式
	private $scoreComment;

	public function run($do)
	{
		$this->$do();
		return redirect('main/index')
			->with('action', $do)
			->with('timeout', $this->runnedTime());
	}
	
	public function 临时()
	{
		$rs=User::where('项目', '环保时装秀')->get();
		foreach ($rs as $row) {
			echo "update students set 编号='{$row->编号}' where id={$row->报名ID};\n";
		}

		$rs=User::where('项目', '环保创意制作')->get();
		foreach ($rs as $row) {
			echo "update students set 编号='{$row->编号}' where id={$row->报名ID};\n";
		}

		die;
	}

	public function 导入报名数据()
	{
		$workDir = matchConfig('全局.工作目录');
		$filename = $workDir . '/导入/报名数据导入.xlsx';
		$filename = gbk($filename);
		$objExcel = \PHPExcel_IOFactory::load( $filename );
		$objSheet = $objExcel->getSheetByName('数据');
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

		//rename
		$now = date("Y m d_H i s");
		rename(gbk($workDir . '/导入/报名数据导入.xlsx'), gbk("$workDir/导入/报名数据导入（已导入{$now}）.xlsx"));
		copy(gbk(base_path('/通用模板/报名数据导入.xlsx')), gbk($workDir.'/导入/报名数据导入.xlsx'));

		return redirect('/')->with('message', "成功，共导入{$insertCount}条数据！耗时：". $this->runnedTime());
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
//		dump(matchConfig('项目'));
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
					$编号 = matchConfig("项目.$item.表名") . $this->letters[$j] . sprintf("%03d", $n);

					$user->编号 = $编号;
					$a=$user->save();

					$n++;
				}//foreach
			}//for j
		}//for i
	}

	public function 生成裁判用表()
	{
		//检测文件是否已经存在
		$savefile=gbk(matchConfig('全局.工作目录').'/裁判用表.xlsx');
		$this->checkFileExist($savefile);

		Template::根据参数生成表格('裁判用表', gbk(matchConfig('全局.工作目录').'/模板/裁判用表模板.xlsx'));

		$arrItems = matchConfig('裁判用表');
		$objExcel = new FillData();
		foreach ($arrItems as $itemName => $item) {
			$data = User::where('项目', $itemName)->orderBy('编号')->get();
			$config = [
//				'objExcel'=>$objTemplateExcel,
				'templateFile'=>gbk(matchConfig('全局.工作目录').'/模板/裁判用表模板.xlsx'),
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
			$objExcel->objSheet->getHeaderFooter()->setOddHeader('&L&"宋体,常规"&14 '. matchConfig('全局.比赛名称') . '　成绩记录表' . "&C&\"微软雅黑,常规\"&18\n{$itemName}" );
			$objExcel->objSheet->getHeaderFooter()->setOddFooter( "&L{$item['表名']} &P/&N页&R裁判员签名_______________ 项目裁判长签名_______________");
			//面边距
			$objExcel->objSheet->getPageMargins()->setHeader(0.3);
			$objExcel->objSheet->getPageMargins()->setTop(0.98);
			$objExcel->objSheet->getPageMargins()->setFooter(0.2);
			$objExcel->objSheet->getPageMargins()->setBottom(0.5);
			$objExcel->objSheet->getPageMargins()->setLeft(0.2);
			$objExcel->objSheet->getPageMargins()->setRight(0.2);
			//缩小打印到一页
			if (matchConfig("裁判用表.$itemName.缩至一页")=='是') {
				$objExcel->printInOnePage();
			}
		}

		//保存
		$objExcel->save($savefile);
	}
	
	public function 生成成绩录入表()
	{
		//保存文件
		$savefile=gbk(matchConfig('全局.工作目录').'/成绩录入.xlsx');
		//检测是否存在
		$this->checkFileExist($savefile);

		$tplfile = gbk(matchConfig('全局.工作目录').'/模板/成绩录入模板.xlsx');
		//生成模板文件
		Template::生成成绩录入表模板($tplfile);

		$items = matchConfig('项目');
		$objExcel = new FillData();
		foreach ($items as $itemName => $item) {
			$objItem = new Item($itemName);
			$data = User::where('项目', $itemName)->orderBy('编号')->get();
			$config = [
//				'objExcel'=>$objTemplateExcel,
				'templateFile'=>$tplfile,
				'sheetName'=>$item['表名'],
				'firstDataRowNum'=>3,
				'data'=>$data,
			];
//			dd($config);
			$objExcel->setConfig($config);
			$objExcel->make();
		}

		//保存
		$objExcel->save($savefile);
	}

	public function 成绩导入()
	{
		//清空原有成绩
		\DB::update("update users set 原始成绩='', 成绩备注=''");

		$filename = gbk(matchConfig('全局.工作目录').'/成绩录入.xlsx');
		$objExcel = \PHPExcel_IOFactory::load( $filename );
		$arrItems = matchConfig('项目');
		foreach ($arrItems as $itemName => $item) {
			$objItem = new Item($itemName);
			$objSheet = $objExcel->getSheetByName($objItem->表名);

			//所有数据
			$arrRows=$objSheet->toArray(null,true,false,false);
			//获取firstDataRowNum,如果B1单元格里有数字就取此值，否则就取值3
			$B1 = (int)trim($arrRows[0][1]);
			$firstDataRowNum = $B1>0 ? $B1 : 3;
			//第一行数据（定位成绩在哪几列）
			$firstRow = array_map('trim', $arrRows[0]);
			//记录成绩所在的列数 例：$arrScoreCol[1]=2，1是“成绩1”,2是所在列数（列数从0开始计数）
			$arrScoreCol=[];
			//记录成绩备注所在的列数 例: $arrMarkCol[1]=2，1是备注1，2是所在列数（列数从0开始计数）
			$arrMarkCol=[];
			for ($i = 1; $i < count($firstRow); $i++) {
				$cellValue = trim($firstRow[$i]);
				if (preg_match('/^\d+$/', $cellValue)) {//成绩
					$arrScoreCol[$cellValue] = $i;
				} elseif (preg_match('/^备注(\d+)$/i', $cellValue, $_array)) {//备注
					$arrMarkCol[$_array[1]] = $i;
				}
			}
			//读取成绩字段所在单元格的备注信息，以确定读取成绩时是否需要保留格式
			$arrScoreComment = [];
			foreach ($arrScoreCol as $scoreNum => $colNum) {
				$comment = $objSheet->getCommentByColumnAndRow($colNum, $firstDataRowNum-1)->getText()->getPlainText();
				$comment = trim($comment);
				$arrScoreComment[$scoreNum] = explode("\n", $comment);
			}

			//确定编号所在列
			$row = $arrRows[$firstDataRowNum-2];
			for ($i = 0; $i < count($row); $i++) {
				if (trim($row[$i]) == '编号') {
					$serialNumberColNum=$i;
				}
			}

			//按行循环
			for ($i = $firstDataRowNum-1; $i < count($arrRows); $i++) {
				$row = array_map('trim', $arrRows[$i]);
				$User = User::where('编号', $row[$serialNumberColNum])->first();
				if (!$User) {
					pp("数据库中没有这个编号：".$row[$serialNumberColNum]);
					pp($arrRows);
					exit();
				}
//				pp($arrScoreCol);/////////////////////

				///////////// 开始：匹配成绩 ////////////
				$arrScores = [];//将所有原始成绩保存在此
				$allScoresIsEmpty = true; //成绩是否全部为空
				foreach ($arrScoreCol as $scoreNum => $colNum) {
					$_score = trim($row[$colNum]);
					//检测成绩是否为空
					if (strlen($_score)) {
						$allScoresIsEmpty = false;
					}
					//判断一下是否需要读取有格式的成绩
					if (in_array('保留格式', $arrScoreComment[$scoreNum])) {
						$_score = $objSheet->getCellByColumnAndRow($colNum, $i+1)->getFormattedValue();
					}
					$arrScores[] = $_score;
				}

				//判断所有成绩是否为空，有一个不是空值就保存
				if (!$allScoresIsEmpty) {
					$User->原始成绩 = serialize($arrScores);
				}
				///////////// 结束：匹配成绩 ////////////

				////////////// 开始：匹配备注////////////
				$arrMarks = [];//保存备注
				$allMarksIsEmpty = true;//成绩备注是否全部为空
				foreach ($arrMarkCol as $markNum => $colNum) {
					$_mark = trim($row[$colNum]);
					//检测备注是否为空
					if (strlen($_mark)) {
						$allMarksIsEmpty = false;
					}
					$arrMarks[] = $_mark;
				}

				//判断所有备注是否为空，有一个不是空值就保存
				if (!$allMarksIsEmpty) {
					$User->成绩备注 = serialize($arrMarks);
				}
				////////////// 结束：匹配备注 ////////////

				//判断成绩、备注是否为空，有一个不为空就要保存
				if (!$allScoresIsEmpty || !$allMarksIsEmpty) {
					$User->save();
				}
			}//for
		}//foreach

		return redirect('main/index')->with('message', '中华人民共和国');
	}

	public function 自定义导入()
	{
		$filename = matchConfig('全局.工作目录') . '/导入/自定义导入.xlsx';
		$filename = gbk($filename);
		$objExcel = \PHPExcel_IOFactory::load( $filename );
		$objSheet = $objExcel->getSheetByName('数据');
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
				if (!$user) {
					dd($arrData[$i][0] . ' 该编号在数据库中未找到');
				}
				$user->$valueField = $value;
				$user->save();
			}
		}
	}

	/**
	 * 比赛完后有学生姓名不正确的
	 */
	public function 更改姓名()
	{
		$file = gbk(matchConfig('全局.工作目录').'/导入/更改姓名.xlsx');
		$objExcel = \PHPExcel_IOFactory::load( $file );
		$objSheet = $objExcel->getSheetByName('数据');
		$arrData=$objSheet->toArray();
		for ($i = 1; $i < count($arrData); $i++) {
			$编号 = trim($arrData[$i][0]);
			$姓名 = trim($arrData[$i][1]);
			$user = User::where('编号',$编号)->first();
			if (!$user) {
				exit($编号 . '未找到 ');
			}
			$user->姓名 = $姓名;
			$user->save();

		}
	}

	public function 添加名单()
	{
		$file = gbk(matchConfig('全局.工作目录').'/导入/添加名单.xlsx');
		$objExcel = \PHPExcel_IOFactory::load( $file );
		$objSheet = $objExcel->getSheetByName('数据');
		$arrData=$objSheet->toArray();
		if (1==count($arrData)) {
			exit('文件中没有数据啊！！');
		}
		for ($i = 1; $i < count($arrData); $i++) {
			$data=[
				'参赛队'=>$arrData[$i][0],
				'姓名'=>$arrData[$i][1],
				'项目'=>$arrData[$i][2],
				'组别'=>$arrData[$i][3],
				'教练'=>$arrData[$i][4],
			];
			$data = array_map('trim', $data);
			$unimports = [];
			if (!strlen($data['姓名']) || !strlen($data['项目']) || !strlen($data['组别'])) {
				$unimports[] = $data;
			}

			$data['编号']=$this->新编号($data['项目'], $data['组别']);
			User::create($data);
			//保存以显示结果
			echo '成功：\t'.$data['编号']."\t".$data['姓名']."<br>";
		}

		echo '以下名单添加不成功，姓名、项目、组别必须填写完整：';
		dump($unimports);

		$time = date("Y-m-d H:i:s");
		rename($file, gbk(matchConfig('全局.工作目录')."/导入/添加名单（已添加{$time}）.xlsx"));
		copy(gbk(base_path('/通用模板/添加名单.xlsx')), gbk(matchConfig('全局.工作目录').'/导入/添加名单.xlsx'));
		
		exit;
	}

	public function 新编号($item, $group)
	{
		$user = User::where('项目', $item)
			->where('组别', $group)
			->orderBy('编号','desc')
			->first();
		if (!$user) {
			dump($item);
			dump($group);
			exit("错误：请检查EXCEL文件中的项目、组别是否正确。");
		}

		$编号 = $user->编号;
		preg_match('/(\d+)$/', $编号, $arr);
		$num=(int)$arr[1] + 1;
		$prefix = preg_replace('/\d+$/', '', $编号);
		return $prefix.sprintf('%03d', $num);
	}


}