<?php
const PAGE_W = 612.0;
const PAGE_H = 936.0;

function pdf_pos(float $x, float $y, float $fs): string
{
    $top = PAGE_H - $y - $fs;
    return sprintf('left: %.2fpt; top: %.2fpt; font-size: %.2fpt;', $x, $top, $fs);
}

function logo_data_uri(string $path): string
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
}

$decisionValue = strtolower(trim((string) ($decision ?? '')));
$reviewedAt = trim((string) ($reviewed_at ?? ''));
$displayDate = $reviewedAt !== '' && $reviewedAt !== '-'
    ? date('F j, Y', strtotime($reviewedAt))
    : date('F j, Y');

$subjectLine = trim((string) ($document['title'] ?? ''));
if ($subjectLine === '') {
    $subjectLine = trim((string) ($document['document_number'] ?? ''));
}
$subjectLine = preg_replace('/\s+/', ' ', $subjectLine) ?? $subjectLine;
$subjectLine = str_replace('"', '', $subjectLine);
$subjectLine = preg_replace('/\bdsfgshdfd+\b/i', '', $subjectLine) ?? $subjectLine;
$subjectLine = preg_replace('/\bdsfg\b/i', '', $subjectLine) ?? $subjectLine;
$subjectLine = preg_replace('/\s{2,}/', ' ', $subjectLine) ?? $subjectLine;
$subjectLine = trim($subjectLine);
if (function_exists('mb_strimwidth')) {
    $subjectLine = mb_strimwidth($subjectLine, 0, 110, '...');
} elseif (strlen($subjectLine) > 110) {
    $subjectLine = substr($subjectLine, 0, 107) . '...';
}

$remarksLine = trim((string) ($remarks ?? ''));
$remarksLine = preg_replace('/\s+/', ' ', $remarksLine) ?? $remarksLine;

$reviewerLine = trim((string) ($reviewer_name ?? ''));
$allowedMark = $decisionValue === 'allowed';
$notAllowedMark = $decisionValue === 'not_allowed';

$logoLeft = '';
$logoRight = '';
$logoBottom = '';
$logoLeftPath = __DIR__ . '/_img17.png';
$logoRightPath = __DIR__ . '/_img19.png';
$logoBottomPath = __DIR__ . '/_img28.jpg';
if (!is_file($logoLeftPath)) {
    $logoLeftPath = __DIR__ . '/_img17.jpg';
}
if (!is_file($logoRightPath)) {
    $logoRightPath = __DIR__ . '/_img19.jpg';
}
if (is_file($logoLeftPath)) {
    $logoLeft = logo_data_uri($logoLeftPath);
}
if (is_file($logoRightPath)) {
    $logoRight = logo_data_uri($logoRightPath);
}
if (is_file($logoBottomPath)) {
    $logoBottom = logo_data_uri($logoBottomPath);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Letter to Commissioner</title>
  <style>
    @page { size: 8.5in 13in; margin: 0; }
    body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; color: #000; }
    .page { position: relative; width: 612pt; height: 936pt; }
    .text { position: absolute; white-space: nowrap; line-height: 1; }
    .wrap { white-space: normal; line-height: 1.2; }
    .f2 { font-family: 'Times New Roman', serif; font-size: 12pt; }
    .f3 { font-family: 'Old English Text MT', 'OldEnglishTextMT', serif; font-size: 18pt; color: #007f00; }
    .f4 { font-family: Arial, sans-serif; font-size: 12pt; }
    .f6 { font-family: 'Brush Script MT', 'Brush Script Std', cursive; font-size: 18pt; }
    .f7 { font-family: 'Times New Roman', serif; font-style: italic; font-weight: 700; font-size: 9.96pt; }
    .f8 { font-family: 'Times New Roman', serif; font-style: italic; font-size: 9.96pt; }
    .f9 { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11.04pt; }
    .f10 { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11.04pt; font-weight: 700; }
    .line { position: absolute; background: #007f00; }
    .logo { position: absolute; }
    .mark { font-family: 'Segoe UI Symbol', 'Segoe UI', Arial, sans-serif; font-weight: 700; font-size: 14pt; }
    .blank-fill { display: inline-block; vertical-align: bottom; border-bottom: 1pt solid #000; padding: 0 2pt; min-height: 12pt; white-space: nowrap; }
    .blank-hon { width: 295pt; }
    .blank-dear { width: 240pt; }
    .blank-subject { width: 250pt; }
    .blank-remarks { width: 250pt; }
  </style>
</head>
<body>
  <div class="page">
    <?php if ($logoLeft !== ''): ?>
      <img class="logo" src="<?= esc($logoLeft) ?>" alt="" style="left: 90.75pt; top: <?= sprintf('%.2fpt', PAGE_H - 834.75 - 77.25) ?>; width: 78.75pt; height: 77.25pt;">
    <?php endif; ?>
    <?php if ($logoRight !== ''): ?>
      <img class="logo" src="<?= esc($logoRight) ?>" alt="" style="left: 454.20pt; top: <?= sprintf('%.2fpt', PAGE_H - 837.40 - 76.85) ?>; width: 82.20pt; height: 76.85pt;">
    <?php endif; ?>
    <?php if ($logoBottom !== ''): ?>
      <img class="logo" src="<?= esc($logoBottom) ?>" alt="" style="left: 249.95pt; top: <?= sprintf('%.2fpt', PAGE_H - 72.252 - 40.35) ?>; width: 290.35pt; height: 40.35pt;">
    <?php endif; ?>

    <div class="line" style="left: 96.10pt; top: <?= sprintf('%.2fpt', PAGE_H - 831.90 - 1) ?>; width: 438.90pt; height: 1pt;"></div>
    <div class="line" style="left: 70.58pt; top: <?= sprintf('%.2fpt', PAGE_H - 115.70 - 3) ?>; width: 470.95pt; height: 3pt;"></div>

    <div class="text f2" style="<?= pdf_pos(237.53, 890.04, 12) ?>">Republic of the Philippines</div>
    <div class="text f3" style="<?= pdf_pos(208.13, 869.28, 18) ?>">Benguet State University</div>
    <div class="text f4" style="<?= pdf_pos(232.25, 854.16, 12) ?>">2601 La Trinidad, Benguet</div>
    <div class="text f6" style="<?= pdf_pos(207.65, 816.22, 18) ?>">Office of the University President</div>

    <div class="text f9" style="<?= pdf_pos(71.90, 768.34, 11.04) ?>"><?= esc($displayDate) ?></div>
    <div class="text f10" style="<?= pdf_pos(72.02, 736.90, 11.04) ?>">HON. <span class="blank-fill blank-hon"><?= esc($reviewerLine) ?></span></div>
    <div class="text f9" style="<?= pdf_pos(72.02, 722.26, 11.04) ?>">Commissioner, Commission on Higher Education</div>
    <div class="text f9" style="<?= pdf_pos(72.02, 707.62, 11.04) ?>">Chairperson, Board of Regents</div>
    <div class="text f9" style="<?= pdf_pos(72.02, 692.98, 11.04) ?>">Benguet State University</div>

    <div class="text f9" style="<?= pdf_pos(72.02, 649.03, 11.04) ?>">Dear Commissioner <span class="blank-fill blank-dear"><?= esc($reviewerLine) ?></span>:</div>

    <div class="text f9" style="<?= pdf_pos(71.90, 617.59, 11.04) ?>">
      This is to respectfully request for the passing of a referendum to members of the Board of
    </div>
    <div class="text f9" style="<?= pdf_pos(72.02, 602.95, 11.04) ?>">
      Regents for the approval of the <span class="blank-fill blank-subject"><?= esc($subjectLine) ?></span>
    </div>

    <div class="text f9" style="<?= pdf_pos(72.02, 573.67, 11.04) ?>">
      The executive brief and supporting documents are attached for your reference.
    </div>
    <div class="text f9" style="<?= pdf_pos(71.90, 544.39, 11.04) ?>">Thank you and best regards.</div>
    <div class="text f9" style="<?= pdf_pos(71.90, 500.59, 11.04) ?>">Very truly yours,</div>

    <div class="text f10" style="<?= pdf_pos(71.90, 457.25, 11.04) ?>">KENNETH ALIP LARUAN</div>
    <div class="text f9" style="<?= pdf_pos(71.90, 442.61, 11.04) ?>">University President</div>
    <div class="text f9" style="<?= pdf_pos(71.90, 428.57, 10.56) ?>">Vice Chair, BSU Board of Regents</div>

    <div class="text f9" style="<?= pdf_pos(279.29, 384.77, 11.04) ?>">______ Subject Matter ALLOWED for Referendum</div>
    <div class="text f9" style="<?= pdf_pos(279.05, 370.13, 11.04) ?>">______ Subject Matter NOT ALLOWED for Referendum</div>
    <?php if ($allowedMark): ?>
      <div class="text mark" style="<?= pdf_pos(279.29, 384.77, 12) ?>">×</div>
    <?php endif; ?>
    <?php if ($notAllowedMark): ?>
      <div class="text mark" style="<?= pdf_pos(279.05, 370.13, 12) ?>">×</div>
    <?php endif; ?>

    <div class="text f9" style="<?= pdf_pos(276.17, 340.85, 11.04) ?>">Remarks: <span class="blank-fill blank-remarks"><?= esc($remarksLine) ?></span></div>

    <?php if (!empty($signature_image)): ?>
      <div style="position: absolute; left: 103.58pt; top: <?= sprintf('%.2fpt', PAGE_H - 271 - 45) ?>; width: 250pt; height: 45pt;">
        <img src="<?= esc($signature_image) ?>" alt="Signature" style="width: 250pt; height: auto;">
      </div>
    <?php endif; ?>

    <div class="text f10" style="<?= pdf_pos(72.02, 253.10, 11.04) ?>">HON. <span class="blank-fill blank-hon"><?= esc($reviewerLine) ?></span></div>
    <div class="text f9" style="<?= pdf_pos(71.90, 238.46, 11.04) ?>">CHED Commissioner</div>
    <div class="text f9" style="<?= pdf_pos(71.90, 223.82, 11.04) ?>">Chair, BSU Board of Regents</div>

    <div class="text f7" style="<?= pdf_pos(72.02, 101.42, 9.96) ?>">Email:</div>
    <div class="text f8" style="<?= pdf_pos(102.86, 101.42, 9.96) ?> color: #0000ff;">president@bsu.edu.ph</div>
    <div class="text f7" style="<?= pdf_pos(72.02, 89.90, 9.96) ?>">Website:</div>
    <div class="text f8" style="<?= pdf_pos(110.06, 89.90, 9.96) ?>">www.bsu.edu.ph</div>
    <div class="text f7" style="<?= pdf_pos(72.02, 78.36, 9.96) ?>">Tel. No.:</div>
    <div class="text f8" style="<?= pdf_pos(111.02, 78.36, 9.96) ?>">+63-974-4222-281</div>
    <div class="text f7" style="<?= pdf_pos(72.02, 66.84, 9.96) ?>">FB Page:</div>
    <div class="text f8" style="<?= pdf_pos(114.26, 66.84, 9.96) ?>">BSU- Office of the President</div>
  </div>
</body>
</html>
