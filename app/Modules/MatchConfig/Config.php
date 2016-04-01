<?php namespace App\Modules\MatchConfig;

use Illuminate\Support\Facades\Cache;

include_once app_path('/Libs/PHPExcel/PHPExcel.php');

class Config {
	
	public static $config;
	
	public static function read()
	{
		if (self::$config) {
			return self::$config;
		}
		
		$config = [];//保存结果

		$objExcel = \PHPExcel_IOFactory::load(gbk(configFilePath(Cache::get('配置文件'))));
		//只有：名称、值 两项的表
		$arrSheets = ['全局'];
		foreach ($arrSheets as $sheetName) {
			$objSheet = $objExcel->getSheetByName($sheetName);
			$rs = $objSheet->toArray();
			for ($i = 2; $i < count($rs); $i++) {
				$config[$sheetName][$rs[$i][0]] = trim($rs[$i][1]);
			}
		}
		
		//根据标题确定值的表，字段数相同的表
		$arrSheets = ['项目','成绩'];
		foreach ($arrSheets as $sheetName) {
			$objSheet = $objExcel->getSheetByName($sheetName);
			$rs = $objSheet->toArray();
			$fields = $rs[1];
			for ($i = 2; $i < count($rs); $i++) {
				$row = $rs[$i];
				$itemName = trim($row[0]);
				if (strlen($itemName)) {
					for ($j = 1; $j < count($row); $j++) {
						$field = trim($fields[$j]);
						$cellValue = $row[$j];
						$config[$sheetName][$itemName][$field] = trim($cellValue);
					}
				}
			}
		}
		
		//读取裁判用表表
		$sheetNames=['裁判用表','成绩册'];
		foreach ($sheetNames as $sheetName) {
			$objSheet = $objExcel->getSheetByName($sheetName);
			$rs = $objSheet->toArray();
			$fields = $rs[1];
			for ($i = 2; $i < count($rs); $i++) {    //按行
				$row = $rs[$i];
				$itemName = $row[0];
				for ($j = 1; $j < count($row); $j++) {    //按列
					$field = trim($fields[$j]);
					if ($row[$j] != null) {
						$objCell = $objSheet->getCellByColumnAndRow($j, $i + 1);
						$cellValue = $row[$j];
						if (strlen($cellValue)) {
							if (preg_match('/^字段(\d+)$/', $field, $arrPreg)) {    //字段n
								$config[$sheetName][$itemName]['字段'][$arrPreg[1]] = $cellValue;
							} elseif (preg_match('/^宽度(\d+)$/', $field, $arrPreg)) {    //字段宽度n
								$config[$sheetName][$itemName]['字段宽度'][$arrPreg[1]] = $cellValue;
								//字段值n
							} elseif (preg_match('/^字段值(\d+)$/', $field, $arrPreg)) {
								$config[$sheetName][$itemName]['字段值'][$arrPreg[1]] = $objCell->getValue();
								//保存单元格名称，以便根据名称获取其格式
								$config[$sheetName][$itemName]['字段格式'][$arrPreg[1]] = \PHPExcel_Cell::stringFromColumnIndex($j) . ($i + 1);
							} else {
								$config[$sheetName][$itemName][$field] = $cellValue;    //其它值
							}
						}
					}//if
				}//for
			}//for
		}//foreach
		
		//把字符串形式的“组别”转化为数组
		foreach ($config['项目'] as $item=>$itemConfig) {
			foreach ($itemConfig as $_k => $_config) {
				if ($_k == '组别') {
					$config['项目'][$item][$_k]=preg_split('/\s+/', $_config);
				}
			}
		}

		//把“成绩/获奖比例”转化为数组
		// dd($config['项目']);
		//dd($config['成绩']);
		foreach ($config['成绩'] as $item => $itemConfig) {
			foreach ($itemConfig as $_k => $_config) {
				if ($_k=='获奖比例') {
					$array = preg_split('/\s+/', trim($_config));
					$_arrResult = [];
					for ($i=0; $i < count($array)/2; $i++) {
						$_arrResult[$array[$i*2]] = $array[$i*2+1];
					}
					// dd($_arrResult);
					$config['成绩'][$item][$_k] = $_arrResult;
				}
			}
		}

		//返回值
		self::$config = $config;
		return $config;
	}
}

