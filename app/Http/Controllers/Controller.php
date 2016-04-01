<?php namespace App\Http\Controllers;

require app_path().'/Libs/PHPExcel/PHPExcel.php';
require app_path().'/Libs/functions.php';

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public $letters = array ( 0 => 'A', 1 => 'B', 2 => 'C', 3 => 'D', 4 => 'E', 5 => 'F', 6 => 'G', 7 => 'H', 8 => 'I', 9 => 'J', 10 => 'K', 11 => 'L', 12 => 'M', 13 => 'N', 14 => 'O', 15 => 'P', 16 => 'Q', 17 => 'R', 18 => 'S', 19 => 'T', 20 => 'U', 21 => 'V', 22 => 'W', 23 => 'X', 24 => 'Y', 25 => 'Z',);

    //储存EXCEL比赛配置文件中的值
//    public $config;
    
//    public function __construct()
//    {
//        $this->config = $this->readConfig();
//    }
    
    /**
     * 读取配置信息（EXCEL）
     * @return array
     */
/*    public function readConfig() {
        $config = [];

        $objExcel = \PHPExcel_IOFactory::load(gbk(configFilePath(Cache::get('配置文件'))));
        //只有：名称、值 两项的表
        $arrSheets = ['全局', '路径'];
        foreach ($arrSheets as $sheetName) {
            $objSheet = $objExcel->getSheetByName($sheetName);
            $rs = $objSheet->toArray();
            for ($i = 2; $i < count($rs); $i++) {
                $config[$sheetName][$rs[$i][0]] = trim($rs[$i][1]);
            }
        }

        //根据标题确定值的表，字段数相同的表
        $arrSheets = ['项目'];
        foreach ($arrSheets as $sheetName) {
            $objSheet = $objExcel->getSheetByName($sheetName);
            $rs = $objSheet->toArray();
            $fields = $rs[1];
            for ($i = 2; $i < count($rs); $i++) {
                $row = $rs[$i];
                $itemName = $row[0];
                for ($j = 1; $j < count($row); $j++) {
                    $field = trim($fields[$j]);
                    $cellValue = $row[$j];
                    $config[$sheetName][$itemName][$field] = $cellValue;
                }
            }
        }

        //读取裁判用表表
        $sheetName='裁判用表';
        $objSheet = $objExcel->getSheetByName($sheetName);
        $rs = $objSheet->toArray();
        $fields = $rs[1];
        for ($i = 2; $i < count($rs); $i++) {	//按行
            $row = $rs[$i];
            $itemName = $row[0];
            for ($j = 1; $j < count($row); $j++) {	//按列
                $field = trim($fields[$j]);
                if ($row[$j]!=null) {
                    $objCell = $objSheet->getCellByColumnAndRow($j,$i+1);
                    $cellValue = $row[$j];

                    if (preg_match('/^字段(\d+)$/', $field, $arrPreg)) {	//字段n
                        $config[$sheetName][$itemName]['字段'][$arrPreg[1]] = $cellValue;
                    } elseif (preg_match('/^宽度(\d+)$/', $field, $arrPreg)) {	//字段宽度n
                        $config[$sheetName][$itemName]['字段宽度'][$arrPreg[1]] = $cellValue;
                        //字段值n
                    } elseif (preg_match('/^字段值(\d+)$/', $field, $arrPreg)) {
                        $config[$sheetName][$itemName]['字段值'][$arrPreg[1]] = $objCell->getValue();
                        //保存单元格名称，以便根据名称获取其格式
                        $config[$sheetName][$itemName]['字段格式'][$arrPreg[1]] = \PHPExcel_Cell::stringFromColumnIndex($j).($i+1);
                    } else {
                        $config[$sheetName][$itemName][$field] = $cellValue;	//其它值
                    }
                }
            }
        }

        return $config;
    }
*/
    
    public static function checkFileExist($file)
    {
        if (is_file($file)) {
            dd(utf8($file) . " 已经存在，无法覆盖生成！");
        }
    }
    
    public function runnedTime()
    {
        return microtime(true) - LARAVEL_START;
    }
}
