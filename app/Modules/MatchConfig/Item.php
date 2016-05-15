<?php namespace App\Modules\MatchConfig;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/3/29
 * Time: 16:26
 * 将EXCEL配置文件中的“项目”转化为对象，易于方便
 */

class Item {
	public $名称;
	public $表名;
	public $组别;
	public $号位;
	public $分组;
//	public $成绩排序;
//	public $排序方式;
//	public $成绩显示格式;

	public function __construct($name)
	{
		$this->名称 = $name;

		$item = matchConfig("项目.$name");
		$this->表名 = $item['表名'];
		$this->组别 = $item['组别'];
		$this->号位 = $item['号位'];
		$this->分组 = $item['分组'];
//		$this->成绩排序 = $item['成绩排序'];
//		$this->排序方式 = $item['排序方式'];
//		$this->成绩显示格式 = $item['成绩显示格式'];
	}
}