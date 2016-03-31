<?php namespace App\Modules\Excel;
use App\Modules\MatchConfig\Item;
use Illuminate\Support\Facades\Cache;

/**
* 根据配置信息（比赛的EXCEL配置文件），生成EXCEL模板文件
*/
class Template extends Base {
	//phpexcel对象
	public static $objExcel;

	/**
	 * @param null $saveFile 生成的文件，为空就不生成
	 * @return \PHPExcel
	 * @throws \PHPExcel_Exception
	 */
	public static function 生成裁判用表模板($saveFile = null)
	{
		$matchConfig = \App\Modules\MatchConfig\Config::read();
//		pd($matchConfig);///////////////////////////////////////////////
		$objExcel = \PHPExcel_IOFactory::load(gbk(configFilePath(Cache::get('配置文件'))));
		//隐藏所有已有表格，模板也被隐藏掉了，需要判断一下并取消隐藏
		foreach ($objExcel->getWorksheetIterator() as $_sheet) {
			$_sheet->setSheetState(\PHPExcel_Worksheet::SHEETSTATE_HIDDEN);
		}
		//复制“裁判用表”，用于复制格式
		$objSheetConfig = $objExcel->getSheetByName('裁判用表');

		foreach ($matchConfig['裁判用表'] as $item=>$itemConfig) {
//			检查表格在配置文件中是否存在，如果存在就该表取消隐藏，否则新建一个
			$objSheet = $objExcel->getSheetByName($itemConfig['表名']);
			if ($objSheet != null) {
				$objSheet->setSheetState(\PHPExcel_Worksheet::SHEETSTATE_VISIBLE);
			} else {
				$objSheet = $objExcel->createSheet();
				$objSheet->setTitle($itemConfig['表名']);	//设置表名
				$objSheet->getTabColor()->setRGB('000000');	//设置标签颜色
				//设置标题行（第一行）、首条数据行（第二行）
//				for ($i = 0; $i < count($itemConfig['字段']); $i++) {
				//列索引（第几个列）
				$n = 0;
				foreach ($itemConfig['字段'] as $k => $_val) {
//					dd($itemConfig);
					////////标题（第一行）////////
					$colName = \PHPExcel_Cell::stringFromColumnIndex($n);//列名
					$objSheet->getRowDimension(1)->setRowHeight($itemConfig['标题行高']);
					$objSheet->getColumnDimension($colName)->setWidth($itemConfig['字段宽度'][$k]);//设置列宽
					$objCell = $objSheet->getCellByColumnAndRow($n, 1);//单元格对象
					$objCell->setValueExplicit($itemConfig['字段'][$k], \PHPExcel_Cell_DataType::TYPE_STRING);//填值
					//设置格式
					$objCell->getStyle()->applyFromArray(Style::$标题);

					///////数据行（第二行）/////////
					$objSheet->getRowDimension(2)->setRowHeight($itemConfig['数据行高']);//设置行高
					$objCell = $objSheet->getCellByColumnAndRow($n, 2);//单元格对象
					$cellName = $colName.'2';//单元格名称
					if (isset($itemConfig['字段值'][$k])) {
						//设置值
						$objCell->setValueExplicit($itemConfig['字段值'][$k], \PHPExcel_Cell_DataType::TYPE_STRING);
						//复制格式
						$cellStyle = $objSheetConfig->getCell($itemConfig['字段格式'][$k])->getStyle();
						$objSheet->duplicateStyle($cellStyle, "$cellName:$cellName");
					}
					//设置格式
					$objCell->getStyle()->applyFromArray(Style::$细边框);

					$n++;
				}
			}
		}

		//save
		if ($saveFile!=null) {
			$objWriter = new \PHPExcel_Writer_Excel5($objExcel);
			$objWriter->save($saveFile);
		}

		return $objExcel;
	}


	public static function 生成成绩录入表模板($saveFile = null)
	{
		$objExcel = \PHPExcel_IOFactory::load(gbk(base_path('通用模板/成绩录入.xls')));
		$objSheet = $objExcel->getSheetByName('模板');

		$items = matchConfig('项目');
		foreach ($items as $itemName => $item) {
			$objItem = new Item($itemName);
			$newSheet = clone $objSheet;
			$newSheet->setTitle($objItem->表名);
			$newSheet->getTabColor()->setRGB('000000');	//设置标签颜色
			$newSheet->setCellValue('A1', $itemName);//设置项目名称
			$objExcel->addSheet($newSheet);
		}
		//隐藏模板表
		$objSheet->setSheetState(\PHPExcel_Worksheet::SHEETSTATE_HIDDEN);
		//save
		if ($saveFile!=null) {
			$objWriter = new \PHPExcel_Writer_Excel5($objExcel);
			$objWriter->save($saveFile);
		}
		return $objExcel;
	}


}