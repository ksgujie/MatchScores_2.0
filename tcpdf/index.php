<?php
include "tcpdf_min_6_2_12/tcpdf.php";

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
//加密，限制文件被修改
$pdf->SetProtection (['modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble']);

$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, 'i', 8));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, 'i', 9));
//$pdf->setPrintHeader(false);
$pdf->SetHeaderData(PDF_HEADER_LOGO, 10, '2016“放飞梦想”全国青少年纸飞机通讯赛', "安徽省蚌埠市高新教育集团第三实验小学代表");
$pdf->setFooterData(array(0,0,0), array(255,255,255));

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

$pdf->AddPage();

//写一条空行，让第二行与页眉横线之间空开一点
$pdf->SetFont('stsongstdlight', 'B', 8);
$pdf->Write(0, "", '', 0, 'C', true, 0, false, false, 0);

$pdf->SetFont('stsongstdlight', 'B', 20);
$pdf->Write(0, "2016“放飞梦想”全国青少年纸飞机通讯赛 报名表", '', 0, 'C', true, 0, false, false, 0);

$pdf->SetFont('stsongstdlight', '', 10);
$headerHtml = <<<EOF
<table width="100%" border="0" cellpadding="1" cellspacing="0">
  <tr>
    <td>&nbsp;</td>
    <td colspan="2" align="right"><em>生成时间：2016年6月4日 0时20分</em></td>
  </tr>
  <tr>
    <td width="20%"><strong>参赛队（每页盖章）</strong></td>
    <td colspan="2">安徽省蚌埠市高新教育集团第三实验</td>
  </tr>
  
  <tr>
    <td><strong>详细联系地址</strong></td>
    <td width="55%">安徽省蚌埠市高新教育集高新教育集高新教育集团第三</td>
    <td width="25%"><strong>邮政编码</strong> 215300</td>
  </tr>
  <tr>
    <td><strong>领队</strong></td>
    <td>顾杰</td>
    <td><strong>领队电话</strong> 15506250512</td>
  </tr>
  <tr>
    <td><strong>教练</strong></td>
    <td colspan="2">顾杰 顾杰 顾杰 顾杰 顾杰 顾杰 </td>
  </tr>
</table>
EOF;
$pdf->writeHTML($headerHtml, true, false, false, false, '');

$dataHtml = <<<EOF
<table width="100%" border="1" cellspacing="0" cellpadding="1">
<thead>
  <tr>
    <td width="5%" align="center">序号</td>
    <td width="20%">姓名</td>
    <td width="20%">组别</td>
    <td width="45%">参赛项目</td>
    <td width="10%">备注</td>
  </tr>
</thead>  

EOF;

for ($i = 0; $i < 120; $i++) {
	$n=$i+1;
	$dataHtml .= <<<EOF
  <tr>
    <td width="5%" align="center"> $n </td>
    <td width="20%"> 顾杰 </td>
    <td width="20%"> 小学男子 </td>
    <td width="45%"> 纸质手掷飞机三人接力团体赛 </td>
    <td width="10%"> </td>
  </tr>
EOF;
}

$dataHtml.='</table>';

$pdf->SetFont('stsongstdlight', '', 9);
$pdf->writeHTML($dataHtml, true, false, false, false, '');

$pdf->Output('江苏昆山队报名表.pdf', 'I');