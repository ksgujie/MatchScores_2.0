<?php namespace App\Modules\Excel;

class Style {

	public static $左对齐 = [
		'alignment' => array(
			'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
		),
	];

	public static $右对齐 = [
		'alignment' => array(
			'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
		),
	];

	public static $水平居中 = [
		'alignment' => array(
			'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
		),
	];

	public static $垂直居中 = [
		'alignment' => array(
			'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
		),
	];

	public static $垂直靠下 = [
		'alignment' => array(
			'vertical' => \PHPExcel_Style_Alignment::VERTICAL_BOTTOM,
		),
	];

	public static $水平垂直居中 = [
		'alignment' => array(
			'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
			'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
		),
	];

	public static $细边框 = [
		'borders' => array(
			'top' => array(
				'style' => \PHPExcel_Style_Border::BORDER_THIN,
			),
			'right' => array(
				'style' => \PHPExcel_Style_Border::BORDER_THIN,
			),
			'left' => array(
				'style' => \PHPExcel_Style_Border::BORDER_THIN,
			),
			'bottom' => array(
				'style' => \PHPExcel_Style_Border::BORDER_THIN,
			),
		),
	];

	public static $加粗 = [
		'font' => array(
			'bold' => true,
		),
	];

	public static $标题 = [
		'font' => array(
			'bold' => true,
		),

		'alignment' => array(
			'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
			'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
			'wrap'=>true,
		),

		'borders' => array(
			'top' => array(
				'style' => \PHPExcel_Style_Border::BORDER_THIN,
			),
			'right' => array(
				'style' => \PHPExcel_Style_Border::BORDER_THIN,
			),
			'left' => array(
				'style' => \PHPExcel_Style_Border::BORDER_THIN,
			),
			'bottom' => array(
				'style' => \PHPExcel_Style_Border::BORDER_THIN,
			),
		),
	];


	public static $成绩册_项目名称 = [
		'font' => array(
			'bold' => true,
			'size' => 18,
		),

		'alignment' => array(
			'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
			'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
			'wrap'=>false,
		),
	];

	public static $成绩册_标题 = [
		'font' => array(
			'bold' => false,
			'size' => 11,
		),

		'alignment' => array(
			'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
			'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
			'wrap'=>true,
		),
	];
}