<?php
session_start();
date_default_timezone_set('Asia/Manila');
if (!isset($_SESSION['user_id'])) { http_response_code(403); exit(); }

require_once "../config.php";

$week_start = $_GET['week_start'] ?? date('Y-m-d', strtotime('Monday this week'));
$dept       = $_GET['dept'] ?? '';

$dates = [];
for ($i = 0; $i < 6; $i++) {
    $dates[] = date('Y-m-d', strtotime($week_start . " +{$i} days"));
}
$week_end = end($dates);

if ($dept) {
    $stmt = $conn->prepare("SELECT id, name, department FROM employees WHERE is_active=1 AND department=? ORDER BY name");
    $stmt->bind_param("s", $dept);
} else {
    $stmt = $conn->prepare("SELECT id, name, department FROM employees WHERE is_active=1 ORDER BY department, name");
}
$stmt->execute();
$employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("SELECT emp_id, att_date, TIME_FORMAT(time_in,'%H:%i') AS ti, TIME_FORMAT(time_out,'%H:%i') AS to_ FROM attendance WHERE att_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $week_start, $week_end);
$stmt->execute();
$att = [];
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
    $att[$r['emp_id']][$r['att_date']] = ['in' => $r['ti'], 'out' => $r['to_']];
}
$stmt->close();

$stmt = $conn->prepare("SELECT emp_id, ot_morning, ot_afternoon FROM overtime WHERE week_start=?");
$stmt->bind_param("s", $week_start);
$stmt->execute();
$ot = [];
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) { $ot[$r['emp_id']] = $r; }
$stmt->close();
$conn->close();

$WORK_START = 8 * 60;
$WORK_END   = 17 * 60;
$GRACE      = 10;
$dayNames   = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$TOTAL_COLS = 23;

function colLetter(int $n): string {
    $s = '';
    while ($n > 0) { $n--; $s = chr(65 + ($n % 26)) . $s; $n = intdiv($n, 26); }
    return $s;
}
function xe(string $v): string { return htmlspecialchars($v, ENT_XML1, 'UTF-8'); }
function minToHM(int $m): string {
    if ($m <= 0) return '';
    $h = intdiv($m, 60); $min = $m % 60;
    if ($h > 0 && $min > 0) return "{$h}h {$min}m";
    return $h > 0 ? "{$h}h" : "{$min}m";
}

$sharedStrings = [];
$ssIndex = [];
function ss(string $v): int {
    global $sharedStrings, $ssIndex;
    if (!isset($ssIndex[$v])) { $ssIndex[$v] = count($sharedStrings); $sharedStrings[] = $v; }
    return $ssIndex[$v];
}

$styleXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="9">
    <font><sz val="10"/><color rgb="FF0F1F3D"/><name val="Calibri"/></font>
    <font><sz val="13"/><b/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    <font><sz val="9"/><b/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    <font><sz val="9"/><b/><color rgb="FF0F1F3D"/><name val="Calibri"/></font>
    <font><sz val="9"/><color rgb="FF0F1F3D"/><name val="Calibri"/></font>
    <font><sz val="9"/><b/><color rgb="FF0F1F3D"/><name val="Calibri"/></font>
    <font><sz val="9"/><b/><color rgb="FFB45309"/><name val="Calibri"/></font>
    <font><sz val="9"/><b/><color rgb="FFC62828"/><name val="Calibri"/></font>
    <font><sz val="9"/><b/><color rgb="FF166534"/><name val="Calibri"/></font>
  </fonts>
  <fills count="14">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF0D2F6E"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1245A8"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1A6ED8"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF263238"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1B5E20"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFDBEAFE"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF0F7FF"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFFF7ED"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFEF2F2"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF0FDF4"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>
  </fills>
  <borders count="5">
    <border><left/><right/><top/><bottom/></border>
    <border>
      <left style="thin"><color rgb="FFBFDBFE"/></left>
      <right style="thin"><color rgb="FFBFDBFE"/></right>
      <top style="thin"><color rgb="FFBFDBFE"/></top>
      <bottom style="thin"><color rgb="FFBFDBFE"/></bottom>
    </border>
    <border>
      <left style="thin"><color rgb="FFBFDBFE"/></left>
      <right style="medium"><color rgb="FF1245A8"/></right>
      <top style="thin"><color rgb="FFBFDBFE"/></top>
      <bottom style="thin"><color rgb="FFBFDBFE"/></bottom>
    </border>
    <border>
      <left style="thin"><color rgb="FFBFDBFE"/></left>
      <right style="thin"><color rgb="FFBFDBFE"/></right>
      <top style="medium"><color rgb="FF1245A8"/></top>
      <bottom style="thin"><color rgb="FFBFDBFE"/></bottom>
    </border>
    <border>
      <left style="thin"><color rgb="FFBFDBFE"/></left>
      <right style="medium"><color rgb="FF1245A8"/></right>
      <top style="medium"><color rgb="FF1245A8"/></top>
      <bottom style="thin"><color rgb="FFBFDBFE"/></bottom>
    </border>
  </borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="19">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="2" fillId="5" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="2" fillId="6" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="3" fillId="7" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center" indent="1"/></xf>
    <xf numFmtId="0" fontId="4" fillId="8" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="4" fillId="9" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="5" fillId="8" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" indent="1"/></xf>
    <xf numFmtId="0" fontId="5" fillId="9" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" indent="1"/></xf>
    <xf numFmtId="0" fontId="6" fillId="10" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="7" fillId="11" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="8" fillId="12" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="4" fillId="13" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="4" fillId="13" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="4" fillId="8" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="4" fillId="9" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
  </cellXfs>
</styleSheet>
XML;

$rows   = [];
$merges = [];

$weekLabel = date('M d', strtotime($week_start)) . ' – ' . date('M d, Y', strtotime($week_end));
$deptLabel = $dept ? " · {$dept}" : '';
$rows[]   = ['h' => 28, 'cells' => [['c'=>1,'t'=>'s','v'=>ss("SRA Attendance Report · {$weekLabel}{$deptLabel}"),'s'=>1]]];
$merges[] = 'A1:' . colLetter($TOTAL_COLS) . '1';

$dayCols     = [[4,5],[6,7],[8,9],[10,11],[12,13],[14,15]];
$dayStyleMap = [2,3,2,3,2,4];
$r2cells     = [['c'=>1,'t'=>'s','v'=>ss('EMPLOYEE'),'s'=>3]];
$merges[]    = 'A2:C2';
foreach ($dayNames as $i => $name) {
    [$c1,$c2] = $dayCols[$i];
    $ds = date('M d', strtotime($dates[$i]));
    $r2cells[] = ['c'=>$c1,'t'=>'s','v'=>ss("{$name} ({$ds})"),'s'=>$dayStyleMap[$i]];
    $merges[]  = colLetter($c1).'2:'.colLetter($c2).'2';
}
$r2cells[] = ['c'=>16,'t'=>'s','v'=>ss('SUMMARY'),'s'=>5];
$merges[]  = 'P2:W2';
$rows[]    = ['h'=>20,'cells'=>$r2cells];

$subH = ['#','Name','Dept'];
foreach ($dayNames as $n) { $subH[]='In'; $subH[]='Out'; }
array_push($subH,'Late','Undertime','Present','Absent','Total Hrs','OT Morn','OT Aftn','OT Total');
$r3c = [];
foreach ($subH as $i=>$h) { $r3c[] = ['c'=>$i+1,'t'=>'s','v'=>ss($h),'s'=>6]; }
$rows[] = ['h'=>18,'cells'=>$r3c];

$dataRowIdx = 4;
$prevDept   = null;

foreach ($employees as $emp) {
    $late=0; $under=0; $hrs=0.0; $present=0; $absent=0;

    if ($emp['department'] !== $prevDept) {
        $rows[]   = ['h'=>16,'cells'=>[['c'=>1,'t'=>'s','v'=>ss(strtoupper($emp['department']).' DEPARTMENT'),'s'=>7]]];
        $merges[] = 'A'.$dataRowIdx.':'.colLetter($TOTAL_COLS).$dataRowIdx;
        $prevDept = $emp['department'];
        $dataRowIdx++;
    }

    $even = ($dataRowIdx % 2 === 0);
    $bs   = $even ? 8  : 9;
    $ns   = $even ? 10 : 11;
    $outs = $even ? 17 : 18;

    $cells = [
        ['c'=>1,'t'=>'n','v'=>$emp['id'],'s'=>$bs],
        ['c'=>2,'t'=>'s','v'=>ss($emp['name']),'s'=>$ns],
        ['c'=>3,'t'=>'s','v'=>ss($emp['department']),'s'=>$bs],
    ];

    foreach ($dates as $i=>$d) {
        $r   = ($att[$emp['id']]??[])[$d]??[];
        $ti  = $r['in'] ??'';
        $to  = $r['out']??'';
        [$c1,$c2] = $dayCols[$i];
        $isSat = ($i===5);
        $cs  = $isSat ? 15 : $bs;
        $cs2 = $isSat ? 15 : ($i===4 ? $outs : $bs);

        $cells[] = ['c'=>$c1,'t'=>'s','v'=>ss($ti),'s'=>$cs];
        $cells[] = ['c'=>$c2,'t'=>'s','v'=>ss($to),'s'=>$cs2];

        if ($ti||$to) { $present++; } else { $absent++; }
        if ($ti) { [$h,$m]=array_map('intval',explode(':',$ti)); $late+=max(0,($h*60+$m)-($WORK_START+$GRACE)); }
        if ($to) { [$h,$m]=array_map('intval',explode(':',$to)); $under+=max(0,$WORK_END-($h*60+$m)); }
        if ($ti&&$to) {
            [$ah,$am]=array_map('intval',explode(':',$ti));
            [$bh,$bm]=array_map('intval',explode(':',$to));
            $hrs+=max(0,(($bh*60+$bm)-($ah*60+$am))/60);
        }
    }

    $otM = (float)($ot[$emp['id']]['ot_morning']  ??0);
    $otA = (float)($ot[$emp['id']]['ot_afternoon']??0);

    $cells[] = ['c'=>16,'t'=>'s','v'=>ss(minToHM($late)),  's'=>$late>0       ? 12 : $bs];
    $cells[] = ['c'=>17,'t'=>'s','v'=>ss(minToHM($under)), 's'=>$bs];
    $cells[] = ['c'=>18,'t'=>'n','v'=>$present,            's'=>$bs];
    $cells[] = ['c'=>19,'t'=>'n','v'=>$absent,             's'=>$absent>0     ? 13 : $bs];
    $cells[] = ['c'=>20,'t'=>'n','v'=>round($hrs,1),       's'=>$bs];
    $cells[] = ['c'=>21,'t'=>'n','v'=>$otM,                's'=>$bs];
    $cells[] = ['c'=>22,'t'=>'n','v'=>$otA,                's'=>$bs];
    $cells[] = ['c'=>23,'t'=>'n','v'=>round($otM+$otA,1),  's'=>($otM+$otA)>0 ? 14 : $bs];

    $rows[] = ['h'=>17,'cells'=>$cells];
    $dataRowIdx++;
}

$colWidths = [5,26,10, 9,9, 9,9, 9,9, 9,9, 9,9, 9,9, 10,10,8,8,10,10,12,10];
$colXml = '';
foreach ($colWidths as $i=>$w) { $n=$i+1; $colXml.="<col min=\"{$n}\" max=\"{$n}\" width=\"{$w}\" customWidth=\"1\"/>"; }

$rowsXml = '';
foreach ($rows as $ri=>$rd) {
    $rn=$ri+1; $rowsXml.="<row r=\"{$rn}\" ht=\"{$rd['h']}\" customHeight=\"1\">";
    foreach ($rd['cells'] as $cell) {
        $ref=colLetter($cell['c']).$rn; $s=$cell['s'];
        if ($cell['t']==='s') { $rowsXml.="<c r=\"{$ref}\" t=\"s\" s=\"{$s}\"><v>{$cell['v']}</v></c>"; }
        else                  { $rowsXml.="<c r=\"{$ref}\" s=\"{$s}\"><v>{$cell['v']}</v></c>"; }
    }
    $rowsXml.='</row>';
}

$mergeXml='';
if($merges){$mergeXml='<mergeCells count="'.count($merges).'">'; foreach($merges as $m){$mergeXml.="<mergeCell ref=\"{$m}\"/>"; } $mergeXml.='</mergeCells>';}

$sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheetViews><sheetView workbookViewId="0"><pane xSplit="3" ySplit="3" topLeftCell="D4" activePane="bottomRight" state="frozen"/></sheetView></sheetViews>
  <sheetFormatPr defaultRowHeight="17"/>
  <cols>'.$colXml.'</cols>
  <sheetData>'.$rowsXml.'</sheetData>
  '.$mergeXml.'
</worksheet>';

$ssXml='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($sharedStrings).'" uniqueCount="'.count($sharedStrings).'">';
foreach($sharedStrings as $s){$ssXml.='<si><t xml:space="preserve">'.xe($s).'</t></si>';}
$ssXml.='</sst>';

$workbookXml='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="Attendance" sheetId="1" r:id="rId1"/></sheets>
</workbook>';

$wbRels='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';

$pkgRels='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';

$ct='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';

function makeZip(array $files): string {
    $cd = ''; $data = ''; $offset = 0;
    foreach ($files as $name => $content) {
        $crc   = crc32($content);
        $size  = strlen($content);
        $local = "\x50\x4b\x03\x04\x14\x00\x00\x00\x00\x00\x00\x00\x00\x00"
               . pack('V', $crc) . pack('V', $size) . pack('V', $size)
               . pack('v', strlen($name)) . "\x00\x00" . $name . $content;
        $data .= $local;
        $cd   .= "\x50\x4b\x01\x02\x14\x00\x14\x00\x00\x00\x00\x00\x00\x00\x00\x00"
               . pack('V', $crc) . pack('V', $size) . pack('V', $size)
               . pack('v', strlen($name)) . "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"
               . pack('V', $offset) . $name;
        $offset += strlen($local);
    }
    $cdSize = strlen($cd);
    $eocd   = "\x50\x4b\x05\x06\x00\x00\x00\x00"
             . pack('v', count($files)) . pack('v', count($files))
             . pack('V', $cdSize) . pack('V', $offset) . "\x00\x00";
    return $data . $cd . $eocd;
}

$zipBytes = makeZip([
    '[Content_Types].xml'        => $ct,
    '_rels/.rels'                => $pkgRels,
    'xl/workbook.xml'            => $workbookXml,
    'xl/_rels/workbook.xml.rels' => $wbRels,
    'xl/worksheets/sheet1.xml'   => $sheetXml,
    'xl/sharedStrings.xml'       => $ssXml,
    'xl/styles.xml'              => $styleXml,
]);

$dr = date('MdY', strtotime($week_start)) . '-' . date('MdY', strtotime($week_end));
$fn = "SRA_Attendance_{$dr}" . ($dept ? "_{$dept}" : '') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"{$fn}\"");
header('Content-Length: ' . strlen($zipBytes));
header('Cache-Control: max-age=0');
echo $zipBytes;
exit();
