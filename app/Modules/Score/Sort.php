<?php namespace App\Modules\Score;

use App\Modules\Score\Show;

class Sort
{
	/**
	 * @param $score1
	 * @param null $score2 这是一个无用参数，Calc::填充排序字段()里调用时默认是两个参数，这里只放一个参数会出错
	 * @return mixed
	 */
	public static function 一轮比大小($score)
	{
		return $score;
	}
	/**
	 * 先比最高轮得分，得分高者排前，最高得分相同的再看第二轮得分，两轮成绩相同的，再看最高得分轮的用时长，用时长的排前
	 * @param $inputScore1 成绩1 已补零
	 * @param $inputScore2 成绩2 已补零
	 * @param $timeLen 用时字符串的长度，一般为6或者4；也可为其它长度，比如：用在团体总积分中的排序时长度就为3
	 * @return string
	 */
	public static function 高分低分用时长($inputScore1, $inputScore2, $timeLen = 6)
	{
		$strReg = '/^(\d+)(\d{'. $timeLen .'})$/';
		preg_match($strReg, $inputScore1, $arr);
		$score1 = $arr[1];
		$time1 = $arr[2];
		preg_match($strReg, $inputScore2, $arr);
		$score2 = $arr[1];
		$time2 = $arr[2];

		$maxScore = max($score1, $score2);//最大得分
		$minScore = min($score1, $score2);//最小得分

		//判断出最大得分的用时
		if ($score1 == $maxScore) {
			if ($score1==$score2) {
				$maxScoreTime = max($time1, $time2);
			} else {
				$maxScoreTime = $time1;
			}
		} else {
			$maxScoreTime = $time2;
		}
		//最小得分的用时
		$minScoreTime = $maxScoreTime == $time1 ? $time2 : $time1;

		return join(',',
			[
				Show::补零($maxScore),
				Show::补零($minScore),
				Show::补零($maxScoreTime),
				Show::补零($minScoreTime)
			]
		);
	}

	/**
	 * 赛车成绩：圈数.用时 典型的应用，先比圈数，圈数相同的再看最大圈数用时少的
	 * @param $inputScore1 成绩1 已补零
	 * @param $inputScore2 成绩2 已补零
	 * @param $timeLen 用时字符串的长度，一般为6或者4；也可表示积分的长度，可为其它长度
	 * @return string
	 */
	public static function 高分低分用时短($inputScore1, $inputScore2, $timeLen = 6)
	{
		//自动补零
		$inputScore1 = Show::补零($inputScore1);
		$inputScore2 = Show::补零($inputScore2);
		
		$strReg = '/^(\d+)(\d{'. $timeLen .'})$/';
		preg_match($strReg, $inputScore1, $arr);
		$score1 = $arr[1];
		$time1 = $arr[2];
		preg_match($strReg, $inputScore2, $arr);
		$score2 = $arr[1];
		$time2 = $arr[2];

		$maxScore = max($score1, $score2);//最大得分
		$minScore = min($score1, $score2);//最小得分

		//判断出最大得分的用时
		if ($score1 == $maxScore) {
			if ($score1==$score2) {
				$maxScoreTime = min($time1, $time2);
			} else {
				$maxScoreTime = $time1;
			}
		} else {
			$maxScoreTime = $time2;
		}

		//最小得分的用时
		$minScoreTime = $maxScoreTime == $time1 ? $time2 : $time1;;

		return join(',',
			[
				Show::补零($maxScore),
				Show::补零($minScore),
				Show::补零(1000000000 - $maxScoreTime),
				Show::补零(1000000000 - $minScoreTime),
			]
		);
	}

	/**
	 * 两轮，只比最高轮成绩，先看得分，得分相同看最高轮用时，用时短者胜
	 * 代码复制至“高分低分用时短”，就改了最后一个return后面的代码
	 * @param $inputScore1
	 * @param $inputScore2
	 * @param int $timeLen
	 * @return mixed
	 */
	public static function 最高轮得分大用时短($inputScore1, $inputScore2, $timeLen = 6)
	{
		//自动补零
		$inputScore1 = Show::补零($inputScore1);
		$inputScore2 = Show::补零($inputScore2);

		$strReg = '/^(\d+)(\d{'. $timeLen .'})$/';
		preg_match($strReg, $inputScore1, $arr);
		$score1 = $arr[1];
		$time1 = $arr[2];
		preg_match($strReg, $inputScore2, $arr);
		$score2 = $arr[1];
		$time2 = $arr[2];

		$maxScore = max($score1, $score2);//最大得分
		$minScore = min($score1, $score2);//最小得分

		//判断出最大得分的用时
		if ($score1 == $maxScore) {
			if ($score1==$score2) {
				$maxScoreTime = min($time1, $time2);
			} else {
				$maxScoreTime = $time1;
			}
		} else {
			$maxScoreTime = $time2;
		}

		//最小得分的用时
		$minScoreTime = $maxScoreTime == $time1 ? $time2 : $time1;;

		return join(',',
			[
				Show::补零($maxScore),
				Show::补零(1000000000 - $maxScoreTime),
			]
		);
	}


}//class