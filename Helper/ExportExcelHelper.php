<?php
/**
 * Class ExportExcelHelper
 * 作者: su
 * 时间: 2020/7/4 14:44
 * 备注: composer require phpoffice/phpspreadsheet:1.6.*
 */

namespace App\Helper;


use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExportExcelHelper
{
    /**
     * 导出Excel
     * @param $response
     * @param $data
     * @param $title
     * @param $fileName
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function excelBrowserExport($response,$title,$fileName)
    {

        //文件名称校验
        if (!$fileName) {
            trigger_error('文件名不能为空', E_USER_ERROR);
        }

        $fileName = $fileName.date('YmdHis').'.xlsx';
        ob_start();
        //$fileName = str_replace('+', '%20', urlencode($fileName));
        $response->withHeader('Content-type', 'Content-type:text/csv');
        $response->withHeader('Content-Disposition', 'attachment;filename=' . $fileName);
        $response->withHeader('Cache-Control','max-age=0');
        ob_end_flush();

        //实例化excel类
        if(empty($this->excelObj)){
            $spreadsheet = new Spreadsheet();
        }else{
            $spreadsheet = $this->excelObj;
        }
        $worksheet = $spreadsheet->getActiveSheet();

        //设置工作表标题名称
        $worksheet->setTitle('工作表格1');

        //表头
        //设置单元格内容
        foreach ($title as $key => $value)
        {
            $worksheet->setCellValueByColumnAndRow($key+1, 1, $value);
        }

//        $row = 2; //从第二行开始
//        foreach ($data as $item) {
//            $column = 1;
//            foreach ($item as $value) {
//                $worksheet->setCellValueByColumnAndRow($column, $row, $value);
//                $column++;
//            }
//            $row++;
//        }

        //下载到浏览器
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx'); //按照指定格式生成Excel文件
//        $app_path = dirname(dirname(__FILE__));
        $app_path = BASE_PATH . '/public/excel/';
//        BASE_PATH . '/public/excel/'
        $this->remove_file($app_path);
        $name = $app_path.$fileName;
        $writer->save($name);
        chmod($name,0655);
        return $response->download($name);
    }

    /**
     * 删除excel文件
     * @param $app_path
     */
    public function remove_file($app_path){
//        $dir = $app_path.'/Excels/downloadExcels';
        $dir = $app_path;
        //先删除目录下的文件：
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if($file != '.' && $file != '..') {
                $fullpath = $dir."/".$file;
                $xlsx = substr(strrchr($fullpath, '.'), 1);
                if(!is_dir($fullpath) && $xlsx == 'xlsx') {
                    unlink($fullpath);
                }
            }
        }
        closedir($dh);
    }
}