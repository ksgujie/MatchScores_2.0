<?php namespace App\Http\Controllers;

use App\Modules\Excel\Style;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Input;

class MainController extends Controller {
	
	//储存临时信息，用于程序测试
	private $temp;

	public function boot()
	{
		$match = Cache::get('配置文件');
		$cfgfile = gbk(configFilePath($match));
		if ($match && is_file($cfgfile)) {
			return redirect('/main/index');
		} else {
			return redirect('/main/select');
		}
	}
	
	public function getSelect()
	{
		$files = getFilesListInDir(gbk(base_path('配置文件')));
		$files = utf8($files);
		$selectValuse=[];
		foreach ((array)$files as $file) {
			$selectValuse[$file] = $file;
		}
		return view('select', ['files'=>$selectValuse]);
	}

	public function postSelect()
	{
		$cfgFile = Input::get('file');
		//未选择文件，就返回
		if (empty($cfgFile)) {
			return redirect()->back();
		} else {
			Cache::forever('配置文件', $cfgFile);
			$this->init();
			return redirect('/');
		}
	}

	/**
	 * 初始化：建目录、复制文件
	 */
	public function init()
	{
		//设置数据库名
		config(['database.connections.mysql.database' => matchConfig('全局.数据库')]);

		$workDir = matchConfig('路径.工作目录');
		//如果工作目录已经存在，就停止初始化
		if (is_dir(gbk($workDir))) {
			return;
		}
		//建立一些目录
		$dirs[] = $workDir . '/模板';
		$dirs[] = $workDir . '/导入';
		foreach ($dirs as $dir) {
			$dir = gbk($dir);
			if (!is_dir($dir)) {
				mkdir($dir, 0777, true);
			}
		}

		//复制文件
		copy(gbk(base_path('/通用模板/报名数据导入.xls')), gbk($workDir.'/导入/报名数据导入.xls'));
		copy(gbk(base_path('/通用模板/更改姓名.xls')), gbk($workDir.'/导入/更改姓名.xls'));
		copy(gbk(base_path('/通用模板/添加名单.xls')), gbk($workDir.'/导入/添加名单.xls'));
		copy(gbk(base_path('/通用模板/自定义导入.xls')), gbk($workDir.'/导入/自定义导入.xls'));
	}

	public function getIndex()
	{
		return view('welcome');

		$this->config = $this->readConfig();
		dump($this->config);
		$objExcel = \PHPExcel_IOFactory::load(gbk(configFilePath(Cache::get('配置文件'))));
		//隐藏所有已有表格，模板也被隐藏掉了，需要判断一下并取消隐藏
		foreach ($objExcel->getWorksheetIterator() as $_sheet) {
			$_sheet->setSheetState(\PHPExcel_Worksheet::SHEETSTATE_HIDDEN);
		}
		//复制“裁判用表”，用于复制格式
		$objSheetConfig = $objExcel->getSheetByName('裁判用表');

		foreach ($this->config['裁判用表'] as $item=>$itemConfig) {
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
		$objWriter = new \PHPExcel_Writer_Excel5($objExcel);
		$objWriter->save('g:/a.xls');
		echo 'ok';
	}
	

}