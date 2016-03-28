<?php namespace App\Modules;

/**
* 根据配置信息（比赛的EXCEL配置文件），生成EXCEL模板文件
*/
class Template extends Base {

	/**
	 * @param $config 配置信息（比赛的EXCEL配置文件）
	 * @param null $saveFile 生成EXCEL模板文件
	 * @return null|string 生成的模板文件路径
	 * @throws \PHPExcel_Exception
	 */
	public function Build($config, $saveFile = null)
	{
		$objExcel = \PHPExcel_IOFactory::load(gbk(configFilePath(Cache::get('配置文件'))));
		//隐藏所有已有表格，模板也被隐藏掉了，需要判断一下并取消隐藏
		foreach ($objExcel->getWorksheetIterator() as $_sheet) {
			$_sheet->setSheetState(\PHPExcel_Worksheet::SHEETSTATE_HIDDEN);
		}
		//复制“裁判用表”，用于复制格式
		$objSheetConfig = $objExcel->getSheetByName('裁判用表参数');

		foreach ($config['裁判用表参数'] as $item=>$itemConfig) {
//			检查表格在配置文件中是否存在，如果存在就复制一个过来，否则新建一个
			$objSheet = $objExcel->getSheetByName($itemConfig['表名']);
			if ($objSheet != null) {
				$objSheet->setSheetState(\PHPExcel_Worksheet::SHEETSTATE_VISIBLE);
			} else {
				$objSheet = $objExcel->createSheet();
				$objSheet->setTitle($itemConfig['表名']);	//设置表名
				//设置标题行（第一行）、首条数据行（第二行）
				for ($i = 0; $i < count($itemConfig['字段']); $i++) {
//					dd($itemConfig);
					////////标题（第一行）////////
					$colName = \PHPExcel_Cell::stringFromColumnIndex($i);//列名
					$objSheet->getRowDimension(1)->setRowHeight($itemConfig['标题行高']);
					$objSheet->getColumnDimension($colName)->setWidth($itemConfig['字段宽度'][$i+1]);//设置列宽
					$objCell = $objSheet->getCellByColumnAndRow($i, 1);//单元格对象
					$objCell->setValueExplicit($itemConfig['字段'][$i+1], \PHPExcel_Cell_DataType::TYPE_STRING);//填值
					//设置格式
					$objCell->getStyle()->applyFromArray(Style::$标题);

					///////数据行（第二行）/////////
					$objSheet->getRowDimension(2)->setRowHeight($itemConfig['数据行高']);//设置行高
					$objCell = $objSheet->getCellByColumnAndRow($i, 2);//单元格对象
					$cellName = $colName.'2';//单元格名称
					if (isset($itemConfig['字段值'][$i + 1])) {
						//设置值
						$objCell->setValueExplicit($itemConfig['字段值'][$i+1], \PHPExcel_Cell_DataType::TYPE_STRING);
						//复制格式
						$cellStyle = $objSheetConfig->getCell($itemConfig['字段格式'][$i+1])->getStyle();
						$objSheet->duplicateStyle($cellStyle, "$cellName:$cellName");
					}
					//设置格式
					$objCell->getStyle()->applyFromArray(Style::$细边框);
				}
			}
		}

		//save
		if ($saveFile==null) {
			$path = rtrim($config['路径']['工作目录'], '/') . '模板';
			$path = gbk($path);
			if (!is_dir($path)) {
				mkdir($path, 0777, true);
			}
			$saveFile = $path . gbk('/裁判用表模板.xls');
		}
		$objWriter = new \PHPExcel_Writer_Excel5($objExcel);
		$objWriter->save($saveFile);
		return $saveFile;
	}
}