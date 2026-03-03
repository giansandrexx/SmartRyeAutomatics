<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../config.php");
    exit();
}

require_once __DIR__ . '/../config.php';

$year     = (int)($_GET['year']    ?? date('Y'));
$month    = (int)($_GET['month']   ?? date('n'));
$category = trim($_GET['category'] ?? '');
$status   = trim($_GET['status']   ?? '');

$monthNames = ['','January','February','March','April','May','June',
               'July','August','September','October','November','December'];
$monthLabel = $monthNames[$month] . ' ' . $year;

$prefix = sprintf('%04d-%02d%%', $year, $month);
$sql    = "SELECT * FROM events WHERE date LIKE ?";
$types  = 's';
$params = [$prefix];

if ($category !== '') { $sql .= " AND category = ?"; $params[] = $category; $types .= 's'; }
if ($status   !== '') { $sql .= " AND status = ?";   $params[] = $status;   $types .= 's'; }
$sql .= " ORDER BY date ASC, start_time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$conn->close();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $cat  = strtolower($row['category'] ?? 'other');
    $stat = strtolower($row['status']   ?? 'pending');
    $rows[] = [
        'title'    => $row['title']      ?? '',
        'date'     => $row['date']       ? date('M d, Y', strtotime($row['date']))         : '',
        'day'      => $row['date']       ? date('l',       strtotime($row['date']))         : '',
        'start'    => $row['start_time'] ? date('h:i A',  strtotime($row['start_time']))   : '-',
        'end'      => $row['end_time']   ? date('h:i A',  strtotime($row['end_time']))     : '-',
        'cat'      => ucfirst($cat),
        'stat'     => ucfirst($stat),
        'assignee' => $row['assignee']   ?? '-',
        'notes'    => $row['notes']      ?? '',
        'cat_key'  => $cat,
        'stat_key' => $stat,
    ];
}

$filterParts = [];
if ($category) $filterParts[] = 'Category: ' . ucfirst($category);
if ($status)   $filterParts[] = 'Status: '   . ucfirst($status);
$filterText = $filterParts ? implode('  |  ', $filterParts) : 'All categories & statuses';

$filename = 'SRA_Events_' . $monthNames[$month] . '_' . $year . '_' . date('His') . '.xls';

while (ob_get_level()) ob_end_clean();

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

function xlCell($val, $type = 'String', $styleId = '') {
    $v = htmlspecialchars((string)$val, ENT_XML1, 'UTF-8');
    $s = $styleId ? " ss:StyleID=\"{$styleId}\"" : '';
    return "<Cell{$s}><Data ss:Type=\"{$type}\">{$v}</Data></Cell>";
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:x="urn:schemas-microsoft-com:office:excel">

<Styles>
  <Style ss:ID="title">
    <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="14" ss:FontName="Arial"/>
    <Interior ss:Color="#0D47A1" ss:Pattern="Solid"/>
    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="meta">
    <Font ss:Italic="1" ss:Color="#546E7A" ss:Size="9" ss:FontName="Arial"/>
    <Interior ss:Color="#EEF4FF" ss:Pattern="Solid"/>
    <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="hdr">
    <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="10" ss:FontName="Arial"/>
    <Interior ss:Color="#1565C0" ss:Pattern="Solid"/>
    <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
    <Borders>
      <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FFFFFF"/>
      <Border ss:Position="Right"  ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FFFFFF"/>
    </Borders>
  </Style>
  <Style ss:ID="row0">
    <Font ss:Size="9" ss:FontName="Arial"/>
    <Interior ss:Color="#F8FAFF" ss:Pattern="Solid"/>
    <Alignment ss:Vertical="Center" ss:WrapText="1"/>
    <Borders>
      <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C5D3E8"/>
      <Border ss:Position="Right"  ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C5D3E8"/>
    </Borders>
  </Style>
  <Style ss:ID="row1">
    <Font ss:Size="9" ss:FontName="Arial"/>
    <Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/>
    <Alignment ss:Vertical="Center" ss:WrapText="1"/>
    <Borders>
      <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C5D3E8"/>
      <Border ss:Position="Right"  ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C5D3E8"/>
    </Borders>
  </Style>
  <Style ss:ID="center">
    <Font ss:Size="9" ss:FontName="Arial"/>
    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
    <Borders>
      <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C5D3E8"/>
      <Border ss:Position="Right"  ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C5D3E8"/>
    </Borders>
  </Style>
  <Style ss:ID="cat_meeting">
    <Font ss:Bold="1" ss:Color="#1D4ED8" ss:Size="9" ss:FontName="Arial"/>
    <Interior ss:Color="#DBEAFE" ss:Pattern="Solid"/>
    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="cat_maintenance">
    <Font ss:Bold="1" ss:Color="#B91C1C" ss:Size="9" ss:FontName="Arial"/>
    <Interior ss:Color="#FEE2E2" ss:Pattern="Solid"/>
    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="cat_training">
    <Font ss:Bold="1" ss:Color="#166534" ss:Size="9" ss:FontName="Arial"/>
    <Interior ss:Color="#DCFCE7" ss:Pattern="Solid"/>
    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="cat_inspection">
    <Font ss:Bold="1" ss:Color="#B45309" ss:Size="9" ss:FontName="Arial"/>
    <Interior ss:Color="#FEF3C7" ss:Pattern="Solid"/>
    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="cat_other">
    <Font ss:Bold="1" ss:Color="#7E22CE" ss:Size="9" ss:FontName="Arial"/>
    <Interior ss:Color="#F3E8FF" ss:Pattern="Solid"/>
    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="stat_pending">
    <Font ss:Bold="1" ss:Color="#B45309" ss:Size="9" ss:FontName="Arial"/>
    <Interior ss:Color="#FEF9C3" ss:Pattern="Solid"/>
    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="stat_confirmed">
    <Font ss:Bold="1" ss:Color="#1D4ED8" ss:Size="9" ss:FontName="Arial"/>
    <Interior ss:Color="#DBEAFE" ss:Pattern="Solid"/>
    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="stat_completed">
    <Font ss:Bold="1" ss:Color="#166534" ss:Size="9" ss:FontName="Arial"/>
    <Interior ss:Color="#DCFCE7" ss:Pattern="Solid"/>
    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="stat_cancelled">
    <Font ss:Bold="1" ss:Color="#B91C1C" ss:Size="9" ss:FontName="Arial"/>
    <Interior ss:Color="#FEE2E2" ss:Pattern="Solid"/>
    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="sum_title">
    <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="13" ss:FontName="Arial"/>
    <Interior ss:Color="#0D47A1" ss:Pattern="Solid"/>
    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="sum_hdr">
    <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="10" ss:FontName="Arial"/>
    <Interior ss:Color="#1565C0" ss:Pattern="Solid"/>
    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="sL0"><Font ss:Bold="1" ss:Size="10" ss:FontName="Arial"/><Interior ss:Color="#F0F4FF" ss:Pattern="Solid"/><Alignment ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C5D3E8"/></Borders></Style>
  <Style ss:ID="sC0"><Font ss:Bold="1" ss:Color="#0D47A1" ss:Size="14" ss:FontName="Arial"/><Interior ss:Color="#F0F4FF" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C5D3E8"/></Borders></Style>
  <Style ss:ID="sN0"><Font ss:Italic="1" ss:Color="#546E7A" ss:Size="9" ss:FontName="Arial"/><Interior ss:Color="#F0F4FF" ss:Pattern="Solid"/><Alignment ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C5D3E8"/></Borders></Style>
  <Style ss:ID="sL1"><Font ss:Bold="1" ss:Size="10" ss:FontName="Arial"/><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/><Alignment ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C5D3E8"/></Borders></Style>
  <Style ss:ID="sC1"><Font ss:Bold="1" ss:Color="#0D47A1" ss:Size="14" ss:FontName="Arial"/><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C5D3E8"/></Borders></Style>
  <Style ss:ID="sN1"><Font ss:Italic="1" ss:Color="#546E7A" ss:Size="9" ss:FontName="Arial"/><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/><Alignment ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C5D3E8"/></Borders></Style>
</Styles>

<Worksheet ss:Name="Events">
<Table ss:DefaultRowHeight="18">
  <Column ss:Width="200"/>
  <Column ss:Width="95"/>
  <Column ss:Width="80"/>
  <Column ss:Width="72"/>
  <Column ss:Width="72"/>
  <Column ss:Width="90"/>
  <Column ss:Width="82"/>
  <Column ss:Width="130"/>
  <Column ss:Width="200"/>

  <Row ss:Height="34">
    <Cell ss:StyleID="title" ss:MergeAcross="8">
      <Data ss:Type="String">SRA SCHEDULING — <?php echo strtoupper($monthLabel); ?> EVENTS</Data>
    </Cell>
  </Row>
  <Row ss:Height="16">
    <Cell ss:StyleID="meta" ss:MergeAcross="8">
      <Data ss:Type="String">Generated: <?php echo date('F d, Y  h:i A'); ?>    •    <?php echo htmlspecialchars($filterText, ENT_XML1, 'UTF-8'); ?></Data>
    </Cell>
  </Row>
  <Row ss:Height="6"><Cell><Data ss:Type="String"></Data></Cell></Row>
  <Row ss:Height="22">
    <Cell ss:StyleID="hdr"><Data ss:Type="String">Title</Data></Cell>
    <Cell ss:StyleID="hdr"><Data ss:Type="String">Date</Data></Cell>
    <Cell ss:StyleID="hdr"><Data ss:Type="String">Day</Data></Cell>
    <Cell ss:StyleID="hdr"><Data ss:Type="String">Start Time</Data></Cell>
    <Cell ss:StyleID="hdr"><Data ss:Type="String">End Time</Data></Cell>
    <Cell ss:StyleID="hdr"><Data ss:Type="String">Category</Data></Cell>
    <Cell ss:StyleID="hdr"><Data ss:Type="String">Status</Data></Cell>
    <Cell ss:StyleID="hdr"><Data ss:Type="String">Assigned To</Data></Cell>
    <Cell ss:StyleID="hdr"><Data ss:Type="String">Notes</Data></Cell>
  </Row>

<?php if (empty($rows)): ?>
  <Row ss:Height="22">
    <Cell ss:StyleID="row0" ss:MergeAcross="8">
      <Data ss:Type="String">No events found for <?php echo $monthLabel; ?>.</Data>
    </Cell>
  </Row>
<?php else: ?>
<?php foreach ($rows as $i => $r):
    $base  = 'row'  . ($i % 2);
    $catSt = 'cat_' . $r['cat_key'];
    $stSt  = 'stat_'. $r['stat_key'];
?>
  <Row ss:Height="20">
    <?php echo xlCell($r['title'],    'String', $base); ?>
    <?php echo xlCell($r['date'],     'String', $base); ?>
    <?php echo xlCell($r['day'],      'String', 'center'); ?>
    <?php echo xlCell($r['start'],    'String', 'center'); ?>
    <?php echo xlCell($r['end'],      'String', 'center'); ?>
    <?php echo xlCell($r['cat'],      'String', $catSt); ?>
    <?php echo xlCell($r['stat'],     'String', $stSt); ?>
    <?php echo xlCell($r['assignee'], 'String', $base); ?>
    <?php echo xlCell($r['notes'],    'String', $base); ?>
  </Row>
<?php endforeach; endif; ?>

</Table>
</Worksheet>

<?php
$total     = count($rows);
$pending   = count(array_filter($rows, fn($r) => $r['stat_key'] === 'pending'));
$confirmed = count(array_filter($rows, fn($r) => $r['stat_key'] === 'confirmed'));
$completed = count(array_filter($rows, fn($r) => $r['stat_key'] === 'completed'));
$cancelled = count(array_filter($rows, fn($r) => $r['stat_key'] === 'cancelled'));
$summary = [
    ['Total Events', $total,     'All events in this export'],
    ['Pending',      $pending,   'Awaiting confirmation'],
    ['Confirmed',    $confirmed, 'Confirmed & scheduled'],
    ['Completed',    $completed, 'Successfully done'],
    ['Cancelled',    $cancelled, 'Cancelled events'],
];
?>
<Worksheet ss:Name="Summary">
<Table ss:DefaultRowHeight="18">
  <Column ss:Width="150"/>
  <Column ss:Width="80"/>
  <Column ss:Width="200"/>

  <Row ss:Height="28">
    <Cell ss:StyleID="sum_title" ss:MergeAcross="2">
      <Data ss:Type="String">SUMMARY — <?php echo strtoupper($monthLabel); ?></Data>
    </Cell>
  </Row>
  <Row ss:Height="20">
    <Cell ss:StyleID="sum_hdr"><Data ss:Type="String">Metric</Data></Cell>
    <Cell ss:StyleID="sum_hdr"><Data ss:Type="String">Count</Data></Cell>
    <Cell ss:StyleID="sum_hdr"><Data ss:Type="String">Notes</Data></Cell>
  </Row>
<?php foreach ($summary as $si => $sr): $z = $si % 2; ?>
  <Row ss:Height="26">
    <?php echo xlCell($sr[0], 'String', "sL{$z}"); ?>
    <?php echo xlCell($sr[1], 'Number', "sC{$z}"); ?>
    <?php echo xlCell($sr[2], 'String', "sN{$z}"); ?>
  </Row>
<?php endforeach; ?>

</Table>
</Worksheet>

</Workbook>
<?php exit(); ?>