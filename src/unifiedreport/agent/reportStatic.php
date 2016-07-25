<?php
/*
 Author: Daniele Fognini, Shaheem Azmal, anupam.ghosh@siemens.com
 Copyright (C) 2015, Siemens AG

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 version 2 as published by the Free Software Foundation.
 
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 
 You should have received a copy of the GNU General Public License along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

use PhpOffice\PhpWord\Element\Section;
use \PhpOffice\PhpWord\Shared\Html;
use \PhpOffice\PhpWord\Style;

class ReportStatic
{
  /** @var timeStamp */
  private $timeStamp;

  /** @var tablestyle */
  private $tablestyle = array("borderSize" => 2,
                              "name" => "Arial",
                              "borderColor" => "000000",
                              "cellSpacing" => 5
                             );
 
  function __construct($timeStamp) {
    $this->timeStamp = $timeStamp ?: time();
  }


  /**
   * @param Section $section 
   */
  function reportHeader(Section $section)
  {
    $headerStyle = array("color" => "009999", "size" => 20, "bold" => true);
    $header = $section->addHeader();
    $header->addText(htmlspecialchars("SIEMENS"), $headerStyle);
  }


  /**
   * @param PhpWord $phpWord
   * @param Section $section 
   */
  function reportFooter($phpWord, Section $section)
  { 
    global $SysConf;

    $commitId = $SysConf['BUILD']['COMMIT_HASH'];
    $commitDate = $SysConf['BUILD']['COMMIT_DATE'];
    $styleTable = array('borderSize'=>10, 'borderColor'=>'FFFFFF' );
    $styleFirstRow = array('borderTopSize'=>10, 'borderTopColor'=>'000000');
    $phpWord->addTableStyle('footerTableStyle', $styleTable, $styleFirstRow);
    $footerStyle = array("color" => "000000", "size" => 9, "bold" => true);
    $footerTime = "Gen Date: ".date("Y/m/d H:i:s T", $this->timeStamp);
    $footerCopyright = "Copyright © 2015 Siemens AG - Restricted"; 
    $footerSpace = str_repeat("  ", 7);
    $footerPageNo = "Page {PAGE} of {NUMPAGES}";
    $footer = $section->addFooter(); 
    $table = $footer->addTable("footerTableStyle");
    $table->addRow(200, $styleFirstRow);
    $table->addCell(15000,$styleFirstRow)->addPreserveText(htmlspecialchars("$footerCopyright $footerSpace $footerTime $footerSpace FOSSologyNG Ver:#$commitId-$commitDate $footerSpace $footerPageNo"), $footerStyle); 
  }


  /**
   * @param Section $section 
   */ 
  function clearingProtocolChangeLogTable(Section $section)
  {
    $thColor = array("bgColor" => "E0E0E0");
    $thText = array("size" => 12, "bold" => true);
    $rowWidth = 600;
    $rowWidth1 = 200;
    $cellFirstLen = 2000;
    $cellSecondLen = 4500;
    $cellThirdLen = 9000;

    $heading = "Clearing Protocol Change Log";
    $section->addTitle(htmlspecialchars($heading), 2);

    $table = $section->addTable($this->tablestyle);

    $table->addRow($rowWidth);
    $cell = $table->addCell($cellFirstLen, $thColor)->addText(htmlspecialchars("Last Update"), $thText);
    $cell = $table->addCell($cellSecondLen, $thColor)->addText(htmlspecialchars("Responsible"), $thText);
    $cell = $table->addCell($cellThirdLen, $thColor)->addText(htmlspecialchars("Comments"), $thText);

    $table->addRow($rowWidth1);
    $cell = $table->addCell($cellFirstLen);
    $cell = $table->addCell($cellSecondLen);
    $cell = $table->addCell($cellThirdLen);

    $section->addTextBreak();
  }


  /**
   * @param Section $section 
   */ 
  function assessmentSummaryTable(Section $section)
  {          
    $heading = "Assessment Summary";
    $infoText = "The following table only contains significant obligations, restrictions & risks for a quick overview – all obligations, restrictions & risks according to Section 3 must be considered.";
    
    $thColor = array("bgColor" => "E0E0E0");  
    $infoTextStyle = array("size" => 10, "color" => "000000");
    $leftColStyle = array("size" => 11, "color" => "000000","bold" => true);
    $firstRowStyle1 = array("size" => 10, "bold" => true);
    $rightColStyleBlue = array("size" => 11, "color" => "0000A0","italic" => true);
    $rightColStyleBlack = array("size" => 11, "color" => "000000");
    $rightColStyleBlackWithItalic = array("size" => 11, "color" => "000000","italic" => true);
    
    $cellRowSpan = array("vMerge" => "restart", "valign" => "top");
    $cellRowContinue = array("vMerge" => "continue");    
    $cellColSpan = array("gridSpan" => 4);
    $cellColSpan2 = array("gridSpan" => 3);

    $rowWidth = 200;
    $rowWidth2 = 300;
    $cellFirstLen = 4300;
    $cellSecondLen = 4300;
    $cellThirdLen = 2300;
    $cellFourthLen = 2300;
    $cellFifthLen = 2300;
    $cellLen = 10000;

    $section->addTitle(htmlspecialchars($heading), 2);
    $section->addText(htmlspecialchars($infoText), $infoTextStyle);

    $table = $section->addTable($this->tablestyle);

    $table->addRow($rowWidth);
    $table->addCell($cellFirstLen)->addText(htmlspecialchars(" General assessment"), $leftColStyle, "pStyle");
    $table->addCell($cellLen)->addText(htmlspecialchars(" "), $rightColStyleBlue, "pStyle");

    $table->addRow($rowWidth);
    $table->addCell($cellFirstLen)->addText(htmlspecialchars(" "), $leftColStyle, "pStyle");
    $table->addCell($cellLen)->addText(htmlspecialchars(" "), $rightColStyleBlue, "pStyle");
    
    $table->addRow($rowWidth);
    $table->addCell($cellFirstLen)->addText(htmlspecialchars(" Source / binary integration notes"), $leftColStyle, "pStyle");
    $cell = $table->addCell($cellLen);
    $cell->addCheckBox("nocriticalfiles", htmlspecialchars(" no critical files found, source code and binaries can be used as is"), $rightColStyleBlackWithItalic, "pStyle");
    $cell->addCheckBox("criticalfiles", htmlspecialchars(" critical files found, source code needs to be adapted and binaries possibly re-built"), $rightColStyleBlackWithItalic, "pStyle");

    $table->addRow($rowWidth);
    $table->addCell($cellFirstLen)->addText(htmlspecialchars(" Dependency notes"), $leftColStyle, "pStyle");
    $cell = $table->addCell($cellLen);
    $cell->addCheckBox("nodependenciesfound", htmlspecialchars(" no dependencies found, neither in source code nor in binaries"), $rightColStyleBlackWithItalic, "pStyle");
    $cell->addCheckBox("dependenciesfoundinsourcecode", htmlspecialchars(" dependencies found in source code (see obligations)"), $rightColStyleBlackWithItalic, "pStyle");
    $cell->addCheckBox("dependenciesfoundinbinaries", htmlspecialchars(" dependencies found in binaries (see obligations)"), $rightColStyleBlackWithItalic, "pStyle");

    $table->addRow($rowWidth);
    $table->addCell($cellFirstLen)->addText(htmlspecialchars(" Export restrictions by copyright owner"), $leftColStyle, "pStyle");
    $cell = $table->addCell($cellLen);
    $cell->addCheckBox("noexportrestrictionsfound", htmlspecialchars(" no export restrictions found"), $rightColStyleBlackWithItalic, "pStyle");
    $cell->addCheckBox("exportrestrictionsfound", htmlspecialchars(" export restrictions found (see obligations)"), $rightColStyleBlackWithItalic, "pStyle");

    $table->addRow($rowWidth);
    $table->addCell($cellFirstLen)->addText(htmlspecialchars(" Restrictions for use (e.g. not for Nuclear Power) by copyright owner"), $leftColStyle, "pStyle");
    $cell = $table->addCell($cellLen);
    $cell->addCheckBox("norestrictionsforusefound", htmlspecialchars(" no restrictions for use found"), $rightColStyleBlackWithItalic, "pStyle");
    $cell->addCheckBox("restrictionsforusefound", htmlspecialchars(" restrictions for use found (see obligations)"), $rightColStyleBlackWithItalic, "pStyle");

    $table->addRow($rowWidth, "pStyle");
    $table->addCell($cellFirstLen)->addText(htmlspecialchars(" Additional notes"), $leftColStyle, "pStyle");
    $cell = $table->addCell($cellLen)->addText(htmlspecialchars(" "), $rightColStyleBlue, "pStyle");

    $table->addRow($rowWidth);
    $cell = $table->addCell($cellFirstLen)->addText(htmlspecialchars(" General Risks (optional)"), $leftColStyle, "pStyle");
    $cell = $table->addCell($cellLen)->addText(htmlspecialchars(" "), $rightColStyleBlue, "pStyle");

    $section->addTextBreak();
  }


  /**
   * @param Section $section 
   */ 
  function todoTable(Section $section)
  {   
    $rowStyle = array("bgColor" => "E0E0E0", "spaceBefore" => 0, "spaceAfter" => 0, "spacing" => 0);
    $rowTextStyleLeft = array("size" => 10, "bold" => true);
    $rowTextStyleRight = array("size" => 10, "bold" => false);
    $rowTextStyleRightBold = array("size" => 10, "bold" => true);

    $heading = "Required license compliance tasks";
    $subHeading = "Common obligations, restrictions and risks:";
    $subHeadingInfoText = "  There is a list of common rules which was defined to simplify the To-Dos for development and distribution. The following list contains rules for development, and      distribution which must always be followed!";
    $rowWidth = 5;
    $firstColLen = 500;
    $secondColLen = 15000;
    
    $section->addTitle(htmlspecialchars($heading), 2);
    $section->addTitle(htmlspecialchars($subHeading), 3);
    $section->addText(htmlspecialchars($subHeadingInfoText), $rowTextStyleRight);

    $r1c1 = "1";
    $r2c1 = "1.a";
    $r3c1 = "1.b";
    $r4c1 = "1.c";
    $r5c1 = "2";
    $r6c1 = "2.a";
    $r7c1 = "2.b";
    $r8c1 = "3";
    $r9c1 = "3.a";
    $r10c1 = "3.b";
    $r11c1 = "3.c";
    $r12c1 = "4";
    $r13c1 = "4.a";

    $r1c2 = "Documentation of license conditions and copyright notices in product documentation (ReadMe_OSS)";
    $r2c21 = "All relevant licenses (global and others - see below) must be added to the legal approved Readme_OSS template.";
    $r2c22 = "Remark:";
    $r2c23 = "“Do Not Use” licenses must not be added to the ReadMe_OSS";
    $r3c2 = "Add all copyrights to README_OSS";
    $r4c2 = "Add all relevant acknowledgements to Readme_OSS";
    $r5c21 = "Modifications in Source Code";
    $r5c22 = "If modifications are permitted:";
    $r6c2 = "Do not change or delete Copyright, patent, trademark, attribution notices or any further legal notices or license texts in any files - i.e. neither within any source file of the component package nor in any of its documentation files.";
    $r7c21 = "Document all changes and modifications in source code files with copyright notices:";
    $r7c22 = "Add copyright (including company and date), function, reason for modification in the header.";
    $r7c23 = "Example:";
    $r7c24 = "© Siemens AG, 2013";
    $r7c25 = "March 18th, 2013 Modified helloworld() – fix memory leak";
    $r8c2 = "Obligations and risk assessment regarding distribution";
    $r9c2 = "Ensure that your distribution terms which are agreed with Siemens’ customers (e.g. standard terms, “AGB”, or individual agreements) define that the open source license conditions shall prevail over the Siemens’ license conditions with respect to the open source software (usually this is part of Readme OSS).";
    $r10c2 = "Do not use any names, trademarks, service marks or product names of the author(s) and/or licensors to endorse or promote products derived from this software component without the prior written consent of the author(s) and/or the owner of such rights.";
    $r11c2 = "";
    $r12c2 = "Required wording to be put in the ReadMe_OSS";
    $r13c21 = "The following statement must be added to any manual. The language of the statement is equal to the manual language. Check translation with documentation department.";
    $r13c22 = "English:";
    $r13c23 = "	The product contains, among other things, Open Source Software developed by third parties. The Open Source Software used in the product and the license agreements concerning this software can be found in the Readme_OSS. These Open Source Software files are protected by copyright. Your compliance with those license conditions will entitle you to use the Open Source Software as foreseen in the relevant license. In the event of conflicts between Siemens license conditions and the Open Source Software license conditions, the Open Source Software conditions shall prevail with respect to the Open Source Software portions of the software. The Open Source Software is licensed royalty-free. Insofar as the applicable Open Source Software License Conditions provide for it you can order the source code of the Open Source Software from your Siemens sales contact - against payment of the shipping and handling charges - for a period of at least 3 years since purchase of the Product. We are liable for the Product including the Open Source Software contained in it pursuant to the license conditions applicable to the Product. Any liability for the Open Source Software beyond the program flow intended for the Product is explicitly excluded. Furthermore any liability for defects resulting from modifications to the Open Source Software by you or third parties is excluded. We do not provide any technical support for the Product if it has been modified.";

    $table = $section->addTable($this->tablestyle);

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen, $rowStyle)->addText(htmlspecialchars($r1c1), $rowTextStyleLeft, "pStyle");
    $cell = $table->addCell($secondColLen, $rowStyle)->addText(htmlspecialchars($r1c2), $rowTextStyleRightBold, "pStyle");

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen)->addText(htmlspecialchars($r2c1), $rowTextStyleLeft, "pStyle");
    $cell = $table->addCell($secondColLen);
    $cell->addText(htmlspecialchars($r2c21), $rowTextStyleRight, "pStyle");
    $cell->addText(htmlspecialchars($r2c22), $rowTextStyleRightBold, "pStyle");
    $cell->addText(htmlspecialchars($r2c23),$rowTextStyleRight, "pStyle");

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen)->addText(htmlspecialchars($r3c1), $rowTextStyleLeft, "pStyle");
    $cell = $table->addCell($secondColLen)->addText(htmlspecialchars($r3c2), $rowTextStyleRight, "pStyle");

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen)->addText(htmlspecialchars($r4c1), $rowTextStyleLeft, "pStyle");
    $cell = $table->addCell($secondColLen)->addText(htmlspecialchars($r4c2), $rowTextStyleRight, "pStyle");

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen, $rowStyle)->addText(htmlspecialchars($r5c1), $rowTextStyleLeft, "pStyle");
    $cell = $table->addCell($secondColLen, $rowStyle);
    $cell->addText(htmlspecialchars($r5c21), $rowTextStyleRightBold, "pStyle");
    $cell->addText(htmlspecialchars($r5c22), $rowTextStyleRight, "pStyle");

    $table->addRow($rowWidth, "pStyle");
    $cell = $table->addCell($firstColLen)->addText(htmlspecialchars($r6c1), $rowTextStyleLeft, "pStyle");
    $cell = $table->addCell($secondColLen)->addText(htmlspecialchars($r6c2), $rowTextStyleRight, "pStyle");

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen)->addText(htmlspecialchars($r7c1), $rowTextStyleLeft, "pStyle");
    $cell = $table->addCell($secondColLen);
    $cell->addText(htmlspecialchars($r7c21), $rowTextStyleRight, "pStyle");
    $cell->addText(htmlspecialchars($r7c22), $rowTextStyleRight, "pStyle");
    $cell->addText(htmlspecialchars($r7c23), $rowTextStyleRight, "pStyle");
    $cell->addText(htmlspecialchars($r7c24), $rowTextStyleRight, "pStyle");
    $cell->addText(htmlspecialchars($r7c25), $rowTextStyleRight, "pStyle");
 
    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen, $rowStyle)->addText(htmlspecialchars($r8c1), $rowTextStyleLeft, "pStyle");
    $cell = $table->addCell($secondColLen, $rowStyle)->addText(htmlspecialchars($r8c2), $rowTextStyleRightBold, "pStyle");

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen)->addText(htmlspecialchars($r9c1), $rowTextStyleLeft, "pStyle");
    $cell = $table->addCell($secondColLen)->addText(htmlspecialchars($r9c2), $rowTextStyleRight, "pStyle");

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen)->addText(htmlspecialchars($r10c1), $rowTextStyleLeft, "pStyle");
    $cell = $table->addCell($secondColLen)->addText(htmlspecialchars($r10c2), $rowTextStyleRight, "pStyle");

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen)->addText(htmlspecialchars($r11c1), $rowTextStyleLeft, "pStyle");
    $cell = $table->addCell($secondColLen);
    $cell->addText(htmlspecialchars($r11c2), $rowTextStyleRightBold, "pStyle");

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen, $rowStyle)->addText(htmlspecialchars($r12c1), $rowTextStyleLeft, "pStyle");
    $cell = $table->addCell($secondColLen, $rowStyle)->addText(htmlspecialchars($r12c2), $rowTextStyleRightBold, "pStyle");

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen)->addText(htmlspecialchars($r13c1), $rowTextStyleLeft, "pStyle");
    $cell = $table->addCell($secondColLen);
    $cell->addText(htmlspecialchars($r13c21), $rowTextStyleRightBold, "pStyle");
    $cell->addText(htmlspecialchars($r13c22), $rowTextStyleRight, "pStyle");
    $cell->addText(htmlspecialchars($r13c23), $rowTextStyleRight, "pStyle");

    $section->addTextBreak();
  }

  
  /**
   * @param1 Section $section
   * @param2 array of obloigations  
   */ 
  function todoObliTable(Section $section, $results)
  {
    $firstRowStyle = array("bgColor" => "D2D0CE");
    $firstRowTextStyle = array("size" => 11, "bold" => true);
    $secondRowTextStyle1 = array("size" => 11, "bold" => false);
    $secondRowTextStyle2 = array("size" => 10, "bold" => false);
    $secondRowTextStyle2Bold = array("size" => 10, "bold" => true);
    $firstColStyle = array ("size" => 11 , "bold"=> true, "bgcolor" => "FFFFC2");
    $secondColStyle = array ("size" => 11 , "bold"=> true, "bgcolor"=> "E0FFFF");
    $subHeading = " Additional obligations, restrictions & risks beyond common rules";
    $subHeadingInfoText1 = "  In this chapter you will find the summary of additional license conditions (relevant for development and distribution) for the OSS component. This chapter contains all obligations of included OSS licenses (need to get added manually during component clearing process). The examples below can be removed if not applicable.";

    $cellRowSpan = array("vMerge" => "restart", "valign" => "top","size" => 11 , "bold"=> true, "bgcolor" => "FFFFC2");
    $cellRowContinue = array("vMerge" => "continue","size" => 11 , "bold"=> true, "bgcolor" => "FFFFC2");
    
    $section->addTitle(htmlspecialchars($subHeading), 3);
    $section->addText(htmlspecialchars($subHeadingInfoText1));

    $rowWidth = 200;
    $firstColLen = 2000;
    $secondColLen = 1500;
    $thirdColLen = 9000;
    $fourthColLen = 1500;
    $fifthColLen = 1500; 
   
    $table = $section->addTable($this->tablestyle);
    
    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen, $firstRowStyle)->addText(htmlspecialchars("Obligation"), $firstRowTextStyle);
    $cell = $table->addCell($secondColLen, $firstRowStyle)->addText(htmlspecialchars("License"), $firstRowTextStyle);
    $cell = $table->addCell($thirdColLen, $firstRowStyle)->addText(htmlspecialchars("License section reference and short Description"), $firstRowTextStyle);
    $cell = $table->addCell($fourthColLen, $firstRowStyle)->addText(htmlspecialchars("Focus area for Development "), $firstRowTextStyle);
    $cell = $table->addCell($fifthColLen, $firstRowStyle)->addText(htmlspecialchars("Focus area for Distribution"), $firstRowTextStyle);

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen, $firstColStyle)->addText(htmlspecialchars("Dual Licensing (optional obligation, check with license)"), $firstRowTextStyle);
    $cell = $table->addCell($secondColLen, $secondColStyle);
    $cell = $table->addCell($thirdColLen);
    $cell->addText(htmlspecialchars("Add explicit note to Readme_OSS:"), $secondRowTextStyle2);
    $cell->addText(htmlspecialchars("To the extend files may be licensed under <license1> or <license2>, in this context <license1> has been chosen."), $secondRowTextStyle2);
    $cell->addText(htmlspecialchars("This shall not restrict the freedom of future contributors to choose either <license1> or <license2>.”"), $secondRowTextStyle2);
    $cell = $table->addCell($fourthColLen)->addText(htmlspecialchars("Not applicable"));
    $cell = $table->addCell($fifthColLen)->addText(htmlspecialchars("Not applicable"));

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen, $firstColStyle)->addText(htmlspecialchars("Do not use the following Files"), $firstRowTextStyle);
    $cell = $table->addCell($secondColLen, $secondColStyle);
    $cell = $table->addCell($thirdColLen);
    $cell->addText(htmlspecialchars("<reason for that>"), $secondRowTextStyle2);
    $cell->addText(htmlspecialchars("Filelist:"), $secondRowTextStyle2Bold, $secondRowTextStyle2);
    $cell = $table->addCell($fourthColLen)->addText(htmlspecialchars("Not applicable"));
    $cell = $table->addCell($fifthColLen)->addText(htmlspecialchars("Not applicable"));

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen, $firstColStyle)->addText(htmlspecialchars("Additional binaries found (dependencies)"), $firstRowTextStyle);
    $cell = $table->addCell($secondColLen, $secondColStyle);
    $cell = $table->addCell($thirdColLen);
    $cell->addText(htmlspecialchars("The following dependencies were found in the source code. If the dependencies will be part of the final product, they must be cleared before this component may be used within a Siemens product"), $secondRowTextStyle2);
    $cell->addText(htmlspecialchars("If you want to use the binaries distributed with the source/binaries ((where no corresponding source code is part of the distribution of this component) you must do a clearing also for those components (add them to the Mainline Portal). The license conditions of the additional binaries are NOT part of this clearing protocol."), $secondRowTextStyle1);
    $cell = $table->addCell($fourthColLen)->addText(htmlspecialchars("Not applicable"));
    $cell = $table->addCell($fifthColLen)->addText(htmlspecialchars("Not applicable"));


    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen, $firstColStyle)->addText(htmlspecialchars("Export restrictions"), $firstRowTextStyle);
    $cell = $table->addCell($secondColLen, $secondColStyle);
    $cell = $table->addCell($thirdColLen);
    $cell->addText(htmlspecialchars("Assess potential export restrictions specified in the license text regarding this software component."), $secondRowTextStyle2);
    $cell = $table->addCell($fourthColLen)->addText(htmlspecialchars("Not applicable"));
    $cell = $table->addCell($fifthColLen)->addText(htmlspecialchars("Not applicable"));


    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen, $firstColStyle)->addText(htmlspecialchars("Restrictions for use"), $firstRowTextStyle);
    $cell = $table->addCell($secondColLen, $secondColStyle);
    $cell = $table->addCell($thirdColLen);
    $cell->addText(htmlspecialchars("Assess potential restrictions for use specified in the license text regarding this software component."), $secondRowTextStyle2);
    $cell = $table->addCell($fourthColLen)->addText(htmlspecialchars("Not applicable"));
    $cell = $table->addCell($fifthColLen)->addText(htmlspecialchars("Not applicable"));

    $html = new Html;

    if(!empty($results)){ 
      foreach($results as $key=>$value){
        if(!empty($value)){
          $num = count($value);
          if($num == 1){
            $table->addRow($rowWidth);
            $table->addCell($firstColLen,$firstColStyle)->addText(htmlspecialchars($key), $firstRowTextStyle);
            $table->addCell($secondColLen,$secondColStyle)->addText(htmlspecialchars($value[0]["License Name"]));
            $cell = $table->addCell($thirdColLen);
            $html->addHtml($cell, $value[0]["Text"]);
            $table->addCell($fourthColLen)->addText(htmlspecialchars($value[0]["DevelopmentString"]), $secondRowTextStyle2Bold, array("align" => "center"));
            $table->addCell($fifthColLen)->addText(htmlspecialchars($value[0]["DistributionString"]), $secondRowTextStyle2Bold, array("align" => "center"));
          }
          else{
            $table->addRow($rowWidth);
            $table->addCell($firstColLen,$cellRowSpan)->addText(htmlspecialchars($key), $firstRowTextStyle);
            $table->addCell($secondColLen,$secondColStyle)->addText(htmlspecialchars($value[0]["License Name"]));
            $cell = $table->addCell($thirdColLen);
            $html->addHtml($cell, $value[0]["Text"]);
            $table->addCell($fourthColLen)->addText(htmlspecialchars($value[0]["DevelopmentString"]), $secondRowTextStyle2Bold, array("align" => "center"));
            $table->addCell($fifthColLen)->addText(htmlspecialchars($value[0]["DistributionString"]), $secondRowTextStyle2Bold, array("align" => "center"));
            for($j=1;$j<$num;$j++){
              $table->addRow($rowWidth);
              $table->addCell($firstColLen,$cellRowContinue)->addText(htmlspecialchars($key), $firstRowTextStyle);
              $table->addCell($secondColLen,$secondColStyle)->addText(htmlspecialchars($value[$j]["License Name"]));
              $cell = $table->addCell($thirdColLen);
              $html->addHtml($cell, $value[$j]["Text"]);
              $table->addCell($fourthColLen)->addText(htmlspecialchars($value[$j]["DevelopmentString"]), $secondRowTextStyle2Bold, array("align" => "center"));
              $table->addCell($fifthColLen)->addText(htmlspecialchars($value[$j]["DistributionString"]), $secondRowTextStyle2Bold, array("align" => "center"));
            }
          }
        }
        else{
          $table->addRow($rowWidth);
          $table->addCell($firstColLen,$firstColStyle)->addText(htmlspecialchars($key), $firstRowTextStyle);
          $table->addCell($secondColLen,$secondColStyle);
          $table->addCell($thirdColLen);
          $table->addCell($fourthColLen);
          $table->addCell($fifthColLen);
        }
      }
    }
    $section->addTextBreak();
  }
  
  
  /**
   * @param Section $section 
   */ 
  function basicForClearingReport(Section $section)
  {
    $heading = "Basis for Clearing Report";
    $section->addTitle(htmlspecialchars($heading), 2);
    
    $table = $section->addTable($this->tablestyle);

    $cellRowContinue = array("vMerge" => "continue");
    $firstRowStyle = array("size" => 12, "bold" => true);
    $rowTextStyle = array("size" => 11, "bold" => false);
    
    $cellRowSpan = array("vMerge" => "restart", "valign" => "top");
    $cellColSpan = array("gridSpan" => 2, "valign" => "center");

    $rowWidth = 200;

    $firstColLen = 3500;
    $secondColLen = 7500;
    $thirdColLen = 4500;

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen, $cellRowSpan)->addText(htmlspecialchars("Preparation basis for OSS"), $firstRowStyle);
    $cell = $table->addCell($secondColLen, $cellColSpan);
    $cell->addCheckBox("chkBox1", htmlspecialchars("According to Siemens Legally relevant Steps from LCR to Clearing Report"), $rowTextStyle);
    $cell->addCheckBox("chkBox2", htmlspecialchars("no"), $rowTextStyle);
    $cell = $table->addCell($thirdColLen);

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen, $cellRowContinue);
    $cell = $table->addCell($secondColLen, $cellColSpan);
    $cell->addCheckBox("checkBox1", htmlspecialchars("According to “Common Principles for Open Source License Interpretation” "), $rowTextStyle);
    $cell->addCheckBox("checkBox2", htmlspecialchars("no"), $rowTextStyle);
    $cell = $table->addCell($thirdColLen);

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen, $cellRowSpan)->addText(htmlspecialchars("OSS Source Code"), $firstRowStyle);
    $cell = $table->addCell($thirdColLen)->addText(htmlspecialchars("Link to Upload page of component:"), $rowTextStyle); 
    $cell = $table->addCell($secondColLen, $cellColSpan)->addText(""); 

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen, $cellRowContinue);
    $cell = $table->addCell($thirdColLen)->addText(htmlspecialchars("MD5 hash value of source code:"), $rowTextStyle);
    $cell = $table->addCell($secondColLen, $cellColSpan)->addText(htmlspecialchars("n/a"), $rowTextStyle);

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen)->addText(htmlspecialchars("Result of LCR editor" ), $firstRowStyle);
    $cell = $table->addCell($thirdColLen)->addText(htmlspecialchars("Embedded .xml file which can be checked by the LCR Editor is embedded here:"), $rowTextStyle);
    $cell = $table->addCell($secondColLen, $cellColSpan)->addText(htmlspecialchars("n/a"), $rowTextStyle);
  
    $section->addTextBreak();
  }

  /**
   * @param Section $section 
   * @param $heading 
   */ 
  function getNonFunctionalLicenses(Section $section, $heading)
  {
    $styleFont = array('bold'=>true, 'size'=>10, 'name'=>'Arial');

    $section->addTitle(htmlspecialchars($heading), 2);
    $text = "In this section the files and their licenses can be listed which do not “go” into the delivered “binary”, e.g. /test or /example.";
    $section->addText($text, $styleFont);   
    $section->addTextBreak();
  }

  /** 
   * @param Section $section
   */ 
  function notes(Section $section, $heading)
  {
    $firstColLen = 3500;
    $secondColLen = 8000;
    $thirdColLen = 4000;
    $styleFont = array('bold'=>true, 'size'=>10, 'name'=>'Arial','underline' => 'single');
    $styleFont1 = array('bold'=>false, 'size'=>10, 'name'=>'Arial','underline' => 'single');
    $styleFont2 = array('bold'=>false, 'size'=>10, 'name'=>'Arial');


    $section->addTitle(htmlspecialchars("$heading"), 2); 
    $section->addText("Only such source code of this component may be used-");
    $section->addListItem("which has been checked by and obtained via the Clearing Center or", 1, "Arial", PhpOffice\PhpWord\Style\ListItem::TYPE_SQUARE_FILLED);
    $section->addListItem("which has been submitted to Clearing Support to be checked", 1 , "Arial", PhpOffice\PhpWord\Style\ListItem::TYPE_SQUARE_FILLED);

    $textrun = $section->createTextRun();
    $textrun->addText("Other source code or binaries from the Internet ", $styleFont2);
    $textrun->addText("must not be ", $styleFont);
    $textrun->addText("used.", $styleFont1);
    $section->addText("");
    $section->addText("The following chapters are generated by the source code scanner.");

    $section->addTextBreak(); 
  }

}
