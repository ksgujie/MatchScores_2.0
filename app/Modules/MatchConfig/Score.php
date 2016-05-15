<?php namespace App\Modules\MatchConfig;

class Score {
	
	public $成绩排序;
	public $排序方式;
//	public $显示格式;
	public $获奖比例;
	public $用时长度;
	
	public function __construct($itemName)
	{
		$score = matchConfig("成绩.$itemName");
		$this->成绩排序 = $score['成绩排序'];
		$this->排序方式 = $score['排序方式'];
//		$this->显示格式 = $score['显示格式'];
		$this->获奖比例 = $score['获奖比例'];
		$this->用时长度 = $score['用时长度'];
	}
}