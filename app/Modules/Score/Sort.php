<?php namespace App\Modules\Score;

use App\Modules\Score\Show;

class Sort
{

	//基础函数
	public static function 得出_高低分及用时_适合用时短者胜($inputScore1, $inputScore2, $timeLen=6)
	{
		$flag=false;
		if ($inputScore1 == '-654321' or $inputScore2=='-987654') {
			$flag =true;
		}///////////////////////////////////////////////////////////////////


		//补零
		$inputScore1 = Show::补零($inputScore1);
		$inputScore2 = Show::补零($inputScore2);

		$strReg = '/^([-\d]+)(\d{'. $timeLen .'})$/';
		preg_match($strReg, $inputScore1, $arr);
		$score1 = (int) preg_replace('/^0+/', '', $arr[1]);	//去掉最前面的0,防止有负数
		$time1 = (int)$arr[2];

//		if ($flag) {
//			dd($arr);
//
//		}///////////////////////////////////////////////////////


		preg_match($strReg, $inputScore2, $arr);
		$score2 = (int) preg_replace('/^0+/', '', $arr[1]);	//去掉最前面的0,防止有负数
		$time2 = (int)$arr[2];

		$maxScore = max($score1, $score2);//最大得分
		$minScore = min($score1, $score2);//最小得分

		//判断出最大得分的用时
		if ($score1 == $maxScore) {
			if ($score1==$score2) {
				$maxScoreTime = min($time1, $time2);	//用时短者胜
//				$maxScoreTime = max($time1, $time2);	//用长短者胜
			} else {
				$maxScoreTime = $time1;
			}
		} else {
			$maxScoreTime = $time2;
		}
		//最小得分的用时
		$minScoreTime = $maxScoreTime == $time1 ? $time2 : $time1;

		return [
			'高分'=>$maxScore,
			'低分'=>$minScore,
			'高分用时'=>$maxScoreTime,
			'低分用时'=>$minScoreTime,
		];
	}

	/**
	 * @param $score
	 * @return mixed
	 */
	public static function 一轮比大小($input)
	{
		$inputScore1 = $input['inputScore1'];
		return $inputScore1 >= 0 ? Show::补零($inputScore1) : Show::负数补零($inputScore1);
	}
	/**
	 * 先比最高轮得分，得分高者排前，最高得分相同的再看第二轮得分，两轮成绩相同的，再看最高得分轮的用时长，用时长的排前
	 * @param $inputScore1 成绩1 已补零
	 * @param $inputScore2 成绩2 已补零
	 * @param $timeLen 用时字符串的长度，一般为6或者4；也可为其它长度，比如：用在团体总积分中的排序时长度就为3
	 * @return string
	 */
	public static function 高分低分用时长($input)
	{
		$inputScore1 = $input['inputScore1'];
		$inputScore2 = $input['inputScore2'];
		$timeLen	 = $input['timeLen'];
		
		$arrScore = self::得出_高低分及用时($inputScore1, $inputScore2, $timeLen);
		$高分 = $arrScore['高分'];
		$低分 = $arrScore['高分'];
		$高分用时 = $arrScore['高分用时'];
		$低分用时 = $arrScore['低分用时'];

		return join(',',
			[
				$高分>=0 ? Show::补零($高分) : Show::负数补零($高分),
				$低分>=0 ? Show::补零($低分) : Show::负数补零($低分),
				Show::补零($高分用时),
				Show::补零($低分用时)
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
	public static function 高分低分用时短($input)
	{
		$inputScore1 = $input['inputScore1'];
		$inputScore2 = $input['inputScore2'];
		$timeLen	 = $input['timeLen'];

		$arrScore = self::得出_高低分及用时($inputScore1, $inputScore2, $timeLen);
		$高分 = $arrScore['高分'];
		$低分 = $arrScore['高分'];
		$高分用时 = $arrScore['高分用时'];
		$低分用时 = $arrScore['低分用时'];

		return join(',',
			[
				$高分>=0 ? Show::补零($高分) : Show::负数补零($高分),
				$低分>=0 ? Show::补零($低分) : Show::负数补零($低分),
				Show::补零(1000000000 - $高分用时),
				Show::补零(1000000000 - $低分用时),
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
	public static function 比最高轮_得分大_用时短($input)
	{
		$inputScore1 = $input['inputScore1'];
		$inputScore2 = $input['inputScore2'];
		$timeLen	 = $input['timeLen'];

		$arrScore = self::得出_高低分及用时_适合用时短者胜($inputScore1, $inputScore2, $timeLen);
		$高分 = $arrScore['高分'];
		$低分 = $arrScore['高分'];
		$高分用时 = $arrScore['高分用时'];
		$低分用时 = $arrScore['低分用时'];

		///////////测试////////////
//		if ($inputScore1 == '-654321' or $inputScore2=='-654321') {
//			dd($arrScore);
//		}

		return join(',',
			[
//				Show::补零($高分),
				$高分>=0 ? Show::补零($高分) : Show::负数补零($高分),
				Show::补零(1000000000 - $高分用时),
			]
		);
	}


}//class