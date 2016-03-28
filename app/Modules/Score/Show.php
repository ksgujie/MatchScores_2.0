<?php namespace App\Modules;

use App\Models\User;

class Show
{
	/**
	 * 字符串/数值 前面补零
	 * @param $s 数值
	 * @param int $len 补零后的长度
	 * @return string
	 */
	public static function 补零($s, $len = 10)
	{
		$slen = strlen($s);
		$zero='';
		if ($slen<$len) {
			for ($i = 0; $i < $len - $slen; $i++) {
				$zero .= '0';
			}
		}
		return $zero.$s;
	}

	public static function 标记_跳过空字符串($var)
	{
		//传入空字符串地话，返回空串
		if (!strlen($var)) {
			return '';
		}
	}

	/**
	 * 例：x标记('圈',8101,3, ['×','√',3=>'y']) 返回：8圈 √×√
	 * @param $xName 标记数值前面数的单位
	 * @param $xVar 原始成绩数值
	 * @param $varLen 标记数值的长度
	 * @param $keyVal 标记对应的键值
	 * @param null $glue 键值转换后各值之间的分隔符
	 * @return string
	 */
	public static function x标记($xName, $xVar, $varLen, $keyVal, $glue = null)
	{
		$xVar = (string)$xVar;
		self::标记_跳过空字符串($xVar);

		$x = substr($xVar, 0, strlen($xVar)-$varLen);
		$var = substr($xVar, strlen($xVar)-$varLen, $varLen);

		return $x.$xName.' '. self::标记($var, $keyVal, $glue);
	}


	/**
	 * 例：$this->标记(101, ['×','√']) 返回：√×√
	 * @param $var 原始成绩值
	 * @param $keyVal 相对应的键值
	 * @param null $glue 转换后各数值之间的分隔符
	 * @return string
	 */
	public static function 标记($var, $keyVal, $glue=null)
	{
		$sVar = (string)$var;
		self::标记_跳过空字符串($sVar);

		$rr=[];
		for ($i = 0; $i < strlen($sVar); $i++) {
			$v = $sVar[$i];
			if (isset($keyVal[$v])) {
				$r=$keyVal[$v];
			} elseif (isset($keyVal['default'])) {
				$r=$keyVal['default'];
			} else {
				$r=$v;
			}
			$rr[]=$r;
		}

		$_glue = is_null($glue) ? '' : $glue;
		return implode($_glue, $rr);
	}

	/**
	 * @param $score 原始成绩
	 * @param $xName 分秒前面数值的单位
	 * @param int $timeLen 分秒的长度 一般为6或4
	 * @return string 例：1圈23分45秒67
	 */
	public static function x分秒($score, $xName, $timeLen = 6)
	{
		self::标记_跳过空字符串($score);

		$fenmu = $timeLen==6 ? 1000000 : 10000;
		$x = (int)($score/$fenmu);
		$time = $score - $x*$fenmu;
		$r = $x.$xName.' '. self::分秒($time, $timeLen);
		return $r;
	}

	/**
	 * @param $score
	 * @param int $scoreLen 时间的长度，一般为6,最后两位是秒后面的小数位；如果为4,则无小数位
	 * @return string xx分xx秒xx 或 xx分xx秒
	 */
	public static function 分秒($score, $scoreLen = 6)
	{
		$score = (string)$score;

		self::标记_跳过空字符串($score);

		//6位长时间
		if ($scoreLen == 6) {
			if (strlen($score) > 6) {
				exit(__CLASS__ . '::分秒() 参数时间超过6位');
			}
			if (strlen($score) < 6) {
				$zero = '';
				for ($i = 0; $i < 6 - strlen($score); $i++) {
					$zero .= '0';
				}
				$score = $zero . $score;
			}
			return $score[0] . $score[1] . '分' . $score[2] . $score[3] . '秒' . $score[4] . $score[5];
		}
		//4位长时间
		if ($scoreLen == 4) {
			if (strlen($score) > 4) {
				exit(__CLASS__ . '::分秒() 参数时间超过4位');
			}
			if (strlen($score) < 4) {
				$zero = '';
				for ($i = 0; $i < 4 - strlen($score); $i++) {
					$zero .= '0';
				}
				$score = $zero . $score;
			}

			return $score[0] . $score[1] . '分' . $score[2] . $score[3] . '秒';
		}
	}


}//class