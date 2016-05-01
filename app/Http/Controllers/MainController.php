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

		$workDir = matchConfig('全局.工作目录');
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
		copy(gbk(base_path('/通用模板/报名数据导入.xlsx')), gbk($workDir.'/导入/报名数据导入.xlsx'));
		copy(gbk(base_path('/通用模板/更改姓名.xlsx')), gbk($workDir.'/导入/更改姓名.xlsx'));
		copy(gbk(base_path('/通用模板/添加名单.xlsx')), gbk($workDir.'/导入/添加名单.xlsx'));
		copy(gbk(base_path('/通用模板/自定义导入.xlsx')), gbk($workDir.'/导入/自定义导入.xlsx'));
	}

	public function getIndex()
	{
		$items = array_keys(matchConfig('项目'));
		return view('welcome')->with('items', $items);
	}
	

}