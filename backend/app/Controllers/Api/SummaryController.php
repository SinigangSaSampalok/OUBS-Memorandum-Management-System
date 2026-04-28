<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\DocumentModel;
use App\Models\ReplySlipModel;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use Dompdf\Dompdf;
use Dompdf\Options;

class SummaryController extends BaseController
{
    use ResponseTrait;

    protected $documentModel;
    protected $replySlipModel;
    protected $userModel;

    public function __construct()
    {
        $this->documentModel = new DocumentModel();
        $this->replySlipModel = new ReplySlipModel();
        $this->userModel = new UserModel();
    }

    public function byDocument($documentId)
    {
        if ($this->request->user['user_type'] !== 'oubs') {
            return $this->respond(['error' => 'Access denied'], 403);
        }

        $document = $this->documentModel->find($documentId);
        if (!$document) {
            return $this->respond(['error' => 'Document not found'], 404);
        }

        $actions = $this->replySlipModel
            ->select('reply_slips.*, users.full_name, users.position')
            ->join('users', 'users.id = reply_slips.user_id')
            ->where('reply_slips.document_id', $documentId)
            ->orderBy('reply_slips.date_signed', 'ASC')
            ->findAll();

        return $this->respond([
            'status' => 'success',
            'data' => [
                'document' => $document,
                'actions' => $actions,
            ],
        ]);
    }

    public function download($documentId)
    {
        if ($this->request->user['user_type'] !== 'oubs') {
            return $this->respond(['error' => 'Access denied'], 403);
        }

        $document = $this->documentModel->find($documentId);
        if (!$document) {
            return $this->respond(['error' => 'Document not found'], 404);
        }

        $recipientType = (string) ($document['recipient_type'] ?? '');
        if (!in_array($recipientType, ['bor', 'uac', 'uadmin'], true)) {
            return $this->respond(['error' => 'Summary PDF is only available for BOR, Academic Council, and Administrative Council documents.'], 400);
        }

        $actionsRaw = $this->replySlipModel
            ->select('reply_slips.*, users.full_name, users.position')
            ->join('users', 'users.id = reply_slips.user_id')
            ->where('reply_slips.document_id', $documentId)
            ->orderBy('reply_slips.date_signed', 'ASC')
            ->findAll();

        // Keep only the latest response per user.
        $actionsByUser = [];
        foreach ($actionsRaw as $action) {
            $actionsByUser[(int) $action['user_id']] = $action;
        }
        $actions = array_values($actionsByUser);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $pdfOutput = null;

        try {
            if ($recipientType === 'uadmin') {
                $html = $this->generateUadminSummaryPDF($document, $actions, true, true);
            } elseif ($recipientType === 'uac') {
                $html = $this->generateUacSummaryPDF($document, $actions, true, true);
            } else {
                $html = $this->generateBorSummaryPDF($document, $actions, true, true);
            }
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper([0, 0, 612, 936], 'portrait');
            $dompdf->render();
            $pdfOutput = $dompdf->output();
        } catch (\Throwable $error) {
            log_message('error', 'Summary PDF render failed (full). Retrying with header images only. Error: ' . $error->getMessage());
            try {
                // Keep branding/header images, disable signature images first.
                if ($recipientType === 'uadmin') {
                    $html = $this->generateUadminSummaryPDF($document, $actions, false, true);
                } elseif ($recipientType === 'uac') {
                    $html = $this->generateUacSummaryPDF($document, $actions, false, true);
                } else {
                    $html = $this->generateBorSummaryPDF($document, $actions, false, true);
                }
                $dompdf = new Dompdf($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper([0, 0, 612, 936], 'portrait');
                $dompdf->render();
                $pdfOutput = $dompdf->output();
            } catch (\Throwable $errorHeaderOnly) {
                log_message('error', 'Summary PDF render failed (header-only). Retrying without all images. Error: ' . $errorHeaderOnly->getMessage());
                if ($recipientType === 'uadmin') {
                    $html = $this->generateUadminSummaryPDF($document, $actions, false, false);
                } elseif ($recipientType === 'uac') {
                    $html = $this->generateUacSummaryPDF($document, $actions, false, false);
                } else {
                    $html = $this->generateBorSummaryPDF($document, $actions, false, false);
                }
                $dompdf = new Dompdf($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper([0, 0, 612, 936], 'portrait');
                $dompdf->render();
                $pdfOutput = $dompdf->output();
            }
        }

        $fileNamePrefix = $recipientType === 'uadmin'
            ? 'summary-uadmin-'
            : ($recipientType === 'uac' ? 'summary-uac-' : 'summary-bor-');
        $fileName = $fileNamePrefix . preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) $document['document_number']) . '.pdf';

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->setBody($pdfOutput);
    }

    private function generateUadminSummaryPDF(array $document, array $actions, bool $allowSignatureImage = true, bool $allowHeaderImage = true): string
    {
        $docNo = htmlspecialchars((string) ($document['document_number'] ?? ''));
        $docTitle = htmlspecialchars((string) ($document['title'] ?? ''));
        $headerBannerDataUri = $this->loadHeaderBannerDataUri('uadmin');
        $useHeaderBanner = $allowHeaderImage && $headerBannerDataUri !== '';
        $logoDataUri = $allowHeaderImage ? $this->loadInstitutionLogoDataUri() : '';

        $members = $this->userModel
            ->select('id, full_name, position')
            ->where('user_type', 'uadmin')
            ->where('is_active', 1)
            ->orderBy('full_name', 'ASC')
            ->findAll();

        $actionsByUser = [];
        foreach ($actions as $action) {
            $actionsByUser[(int) $action['user_id']] = $action;
        }

        $rowsHtml = '';
        $approvedTotal = 0;
        $disapprovedTotal = 0;
        foreach ($members as $member) {
            $userId = (int) $member['id'];
            $reply = $actionsByUser[$userId] ?? null;
            $actionValue = (string) ($reply['action'] ?? '');

            if ($actionValue === 'approve') {
                $approvedTotal++;
            } elseif ($actionValue === 'disapprove') {
                $disapprovedTotal++;
            }

            $approvedSignature = '';
            $disapprovedSignature = '';
            if ($allowSignatureImage && !empty($reply['signature_image'])) {
                $signatureDataUri = $this->prepareSignatureForPdf((string) $reply['signature_image']);
                $signatureHtml = $signatureDataUri !== ''
                    ? '<img class="signature-img" src="' . htmlspecialchars($signatureDataUri, ENT_QUOTES, 'UTF-8') . '" alt="Signature" />'
                    : '';
                if ($actionValue === 'approve') {
                    $approvedSignature = $signatureHtml;
                } elseif ($actionValue === 'disapprove') {
                    $disapprovedSignature = $signatureHtml;
                }
            }

            $memberName = htmlspecialchars((string) ($member['full_name'] ?? ''));
            $memberPosition = htmlspecialchars((string) ($member['position'] ?? ''));
            $memberCell = '<div class="member-name">' . $memberName . '</div>';
            if ($memberPosition !== '') {
                $memberCell .= '<div class="member-pos">' . $memberPosition . '</div>';
            }

            $comments = htmlspecialchars((string) ($reply['remarks'] ?? ''));
            $commentsCell = $comments !== '' ? nl2br($comments) : '&nbsp;';

            $rowsHtml .= '
                <tr>
                    <td class="col-member">' . $memberCell . '</td>
                    <td class="col-approve">' . ($approvedSignature !== '' ? $approvedSignature : '&nbsp;') . '</td>
                    <td class="col-disapprove">' . ($disapprovedSignature !== '' ? $disapprovedSignature : '&nbsp;') . '</td>
                    <td class="col-comments">' . $commentsCell . '</td>
                </tr>';
        }

        if ($rowsHtml === '') {
            $rowsHtml = '<tr><td colspan="4" class="empty-row">No Administrative Council members found.</td></tr>';
        } else {
            $approvedTotalCell = $approvedTotal > 0 ? (string) $approvedTotal : '&nbsp;';
            $disapprovedTotalCell = $disapprovedTotal > 0 ? (string) $disapprovedTotal : '&nbsp;';
            $rowsHtml .= '
                <tr class="total-row">
                    <td class="col-member total-label">TOTAL</td>
                    <td class="col-approve total-value">' . $approvedTotalCell . '</td>
                    <td class="col-disapprove total-value">' . $disapprovedTotalCell . '</td>
                    <td class="col-comments">&nbsp;</td>
                </tr>';
        }

        $headerHtml = '';
        if ($useHeaderBanner) {
            $headerHtml = '<div class="header-banner-wrapper"><img class="header-banner-img" src="' . $headerBannerDataUri . '" alt="BSU Header" /></div>';
        } else {
            $headerHtml = '
                <div class="header-fallback">
                    <div class="header-row">
                        <div class="header-left">
                            <div class="header-left-inner">
                                <div class="logo-cell">
                                    ' . ($allowHeaderImage && $logoDataUri !== ''
                                        ? '<img class="logo-img" src="' . $logoDataUri . '" alt="BSU Logo" />'
                                        : '<div class="logo-fallback">BSU</div>') . '
                                </div>
                                <div class="header-text-cell">
                                    <div class="header-title-main">Republic of the Philippines</div>
                                    <div class="header-title-sub">Benguet State University</div>
                                    <div class="header-title-office">OFFICE OF THE UNIVERSITY AND BOARD SECRETARY</div>
                                    <div class="header-title-place">La Trinidad, Benguet</div>
                                </div>
                            </div>
                        </div>
                        <div class="header-right">
                            <table class="doc-code-table">
                                <tr>
                                    <td class="doc-code-label">Document<br>Code:</td>
                                    <td class="doc-code-value">QF-OUBS-16</td>
                                    <td class="doc-code-label">Revision<br>Number:</td>
                                    <td class="doc-code-value doc-code-center">00</td>
                                </tr>
                                <tr>
                                    <td class="doc-code-label">Effectivity:</td>
                                    <td class="doc-code-value">July 17, 2024</td>
                                    <td class="doc-code-value" colspan="2"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>';
        }

        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Administrative Council Summary - ' . $docNo . '</title>
    <style>
        @page {
            size: 8.5in 13in;
            margin: 5mm 7mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            font-size: 9pt;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            width: 100%;
            padding-bottom: 24mm;
            box-sizing: border-box;
        }
        .header-banner-wrapper {
            margin-bottom: 8pt;
        }
        .header-banner-img {
            width: 100%;
            height: auto;
            display: block;
            border: 1pt solid #000;
        }
        .header-fallback {
            border: 1pt solid #000;
            padding: 8pt;
            margin-bottom: 8pt;
        }
        .header-row {
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        .header-left {
            display: table-cell;
            width: 67%;
            vertical-align: middle;
            padding-right: 8pt;
        }
        .header-left-inner {
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        .logo-cell {
            display: table-cell;
            width: 74pt;
            vertical-align: middle;
            text-align: left;
        }
        .logo-img {
            width: 68pt;
            height: 68pt;
            display: block;
            object-fit: cover;
        }
        .logo-fallback {
            width: 64pt;
            height: 64pt;
            border: 1.5pt solid #2f5d26;
            border-radius: 50%;
            text-align: center;
            line-height: 64pt;
            font-size: 10pt;
            color: #2f5d26;
            font-weight: bold;
        }
        .header-text-cell {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        .header-right {
            display: table-cell;
            width: 33%;
            vertical-align: top;
        }
        .header-title-main {
            font-size: 9pt;
        }
        .header-title-sub {
            font-size: 19pt;
            color: #1a5c1e;
            font-family: "Old English Text MT", "Times New Roman", serif;
            font-weight: 700;
            margin-top: 2pt;
        }
        .header-title-office {
            font-size: 10.5pt;
            font-weight: bold;
            font-style: italic;
            letter-spacing: 0.2pt;
            margin-top: 2pt;
        }
        .header-title-place {
            font-size: 9pt;
            margin-top: 1pt;
        }
        .doc-code-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8.5pt;
        }
        .doc-code-table td {
            border: 1px solid #000;
            padding: 2pt 3pt;
            vertical-align: middle;
        }
        .doc-code-label {
            width: 34%;
            font-weight: normal;
            line-height: 1.1;
        }
        .doc-code-value {
            font-weight: normal;
            line-height: 1.1;
        }
        .doc-code-center {
            text-align: center;
        }
        .referendum-title {
            margin: 4pt 0 6pt;
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 12pt;
            margin: 0 0 6pt;
            font-style: italic;
            font-weight: bold;
        }
        .doc-line {
            border: 1px solid #000;
            background: #d7e6c1;
            padding: 5pt 6pt;
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 4pt;
            line-height: 1.25;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #000;
            padding: 4pt;
            vertical-align: middle;
        }
        th {
            background: #ecd282;
            text-align: center;
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .col-member { width: 48%; vertical-align: top; }
        .col-approve { width: 16%; text-align: center; }
        .col-disapprove { width: 16%; text-align: center; }
        .col-comments { width: 20%; vertical-align: top; }
        .member-name {
            font-weight: bold;
            margin-bottom: 1pt;
        }
        .member-pos {
            font-size: 8pt;
            color: #222;
            line-height: 1.2;
        }
        .signature-img {
            max-width: 90pt;
            max-height: 28pt;
            display: inline-block;
        }
        .empty-row {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 12pt 8pt;
        }
        .total-row td {
            font-weight: bold;
        }
        .total-label {
            text-align: right;
            font-style: italic;
            letter-spacing: 0.4pt;
            padding-right: 10pt;
        }
        .total-value {
            font-size: 16pt;
            font-style: italic;
            text-align: center;
            line-height: 1;
            padding: 6pt 4pt;
        }
        .uadmin-footer {
            position: fixed;
            left: 7mm;
            right: 7mm;
            bottom: 5mm;
            font-size: 7pt;
            line-height: 1.2;
        }
        .uadmin-footer-line {
            height: 0;
            border-top: 2pt solid #5fa54b;
            border-bottom: 0.6pt solid #b8d6ae;
            margin-bottom: 3pt;
        }
        .uadmin-footer-contact {
            width: 100%;
            display: table;
            font-size: 7pt;
            font-style: italic;
            font-weight: bold;
        }
        .uadmin-footer-left {
            display: table-cell;
            width: 50%;
            text-align: left;
        }
        .uadmin-footer-right {
            display: table-cell;
            width: 50%;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        ' . $headerHtml . '
        <div class="referendum-title">REFERENDUM <span class="subtitle">(Administrative Council)</span></div>
        <div class="doc-line">' . ($docNo !== '' ? $docNo . ': ' : '') . $docTitle . '</div>
        <table>
            <thead>
                <tr>
                    <th class="col-member">Members</th>
                    <th class="col-approve">Approved</th>
                    <th class="col-disapprove">Disapproved</th>
                    <th class="col-comments">Comments</th>
                </tr>
            </thead>
            <tbody>
                ' . $rowsHtml . '
            </tbody>
        </table>

        <div class="uadmin-footer">
            <div class="uadmin-footer-line"></div>
            <div class="uadmin-footer-contact">
                <div class="uadmin-footer-left">
                    Telephone No. +63-74-422-2009<br>
                    E-mail Address: oubs@bsu.edu.ph
                </div>
                <div class="uadmin-footer-right">
                    Website: www.bsu.edu.ph<br>
                    FB Page: OUBS BSU
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
    }

    private function generateUacSummaryPDF(array $document, array $actions, bool $allowSignatureImage = true, bool $allowHeaderImage = true): string
    {
        $docNo = htmlspecialchars((string) ($document['document_number'] ?? ''));
        $docTitle = htmlspecialchars((string) ($document['title'] ?? ''));
        $headerBannerDataUri = $this->loadHeaderBannerDataUri('uac');
        $useHeaderBanner = $allowHeaderImage && $headerBannerDataUri !== '';
        $logoDataUri = $allowHeaderImage ? $this->loadInstitutionLogoDataUri() : '';

        $members = $this->userModel
            ->select('users.id, users.full_name, users.position, college_campuses.name as college_campus_name')
            ->join('college_campuses', 'college_campuses.id = users.college_campus_id', 'left')
            ->where('users.user_type', 'uac')
            ->where('users.is_active', 1)
            ->orderBy('college_campuses.name', 'ASC')
            ->orderBy('users.full_name', 'ASC')
            ->findAll();

        $actionsByUser = [];
        foreach ($actions as $action) {
            $actionsByUser[(int) $action['user_id']] = $action;
        }

        $groupedMembers = [];
        foreach ($members as $member) {
            $group = trim((string) ($member['college_campus_name'] ?? ''));
            if ($group === '') {
                $group = 'UNASSIGNED COLLEGE/CAMPUS';
            }
            if (!isset($groupedMembers[$group])) {
                $groupedMembers[$group] = [];
            }
            $groupedMembers[$group][] = $member;
        }

        $rowsHtml = '';
        $approvedTotal = 0;
        $disapprovedTotal = 0;

        foreach ($groupedMembers as $groupName => $groupMembers) {
            $rowsHtml .= '
                <tr class="group-row">
                    <td colspan="4">Classification: ' . htmlspecialchars($groupName) . '</td>
                </tr>';

            foreach ($groupMembers as $member) {
                $userId = (int) $member['id'];
                $reply = $actionsByUser[$userId] ?? null;
                $actionValue = (string) ($reply['action'] ?? '');

                if ($actionValue === 'approve') {
                    $approvedTotal++;
                } elseif ($actionValue === 'disapprove') {
                    $disapprovedTotal++;
                }

                $approvedSignature = '';
                $disapprovedSignature = '';
                if ($allowSignatureImage && !empty($reply['signature_image'])) {
                    $signatureDataUri = $this->prepareSignatureForPdf((string) $reply['signature_image']);
                    $signatureHtml = $signatureDataUri !== ''
                        ? '<img class="signature-img" src="' . htmlspecialchars($signatureDataUri, ENT_QUOTES, 'UTF-8') . '" alt="Signature" />'
                        : '';
                    if ($actionValue === 'approve') {
                        $approvedSignature = $signatureHtml;
                    } elseif ($actionValue === 'disapprove') {
                        $disapprovedSignature = $signatureHtml;
                    }
                }

                $memberName = htmlspecialchars((string) ($member['full_name'] ?? ''));
                $memberPosition = htmlspecialchars((string) ($member['position'] ?? ''));
                $comments = htmlspecialchars((string) ($reply['remarks'] ?? ''));
                $commentsCell = $comments !== '' ? nl2br($comments) : '&nbsp;';

                $memberCell = '<div class="member-name">' . ($memberName !== '' ? $memberName : '&nbsp;') . '</div>';
                if ($memberPosition !== '') {
                    $memberCell .= '<div class="member-pos">' . $memberPosition . '</div>';
                }

                $rowsHtml .= '
                    <tr>
                        <td class="col-member">' . $memberCell . '</td>
                        <td class="col-approve">' . ($approvedSignature !== '' ? $approvedSignature : '&nbsp;') . '</td>
                        <td class="col-disapprove">' . ($disapprovedSignature !== '' ? $disapprovedSignature : '&nbsp;') . '</td>
                        <td class="col-comments">' . $commentsCell . '</td>
                    </tr>';
            }
        }

        if ($rowsHtml === '') {
            $rowsHtml = '<tr><td colspan="4" class="empty-row">No Academic Council members found.</td></tr>';
        } else {
            $approvedTotalCell = $approvedTotal > 0 ? (string) $approvedTotal : '&nbsp;';
            $disapprovedTotalCell = $disapprovedTotal > 0 ? (string) $disapprovedTotal : '&nbsp;';
            $rowsHtml .= '
                <tr class="total-row">
                    <td class="total-label">TOTAL</td>
                    <td class="total-value">' . $approvedTotalCell . '</td>
                    <td class="total-value">' . $disapprovedTotalCell . '</td>
                    <td class="col-comments">&nbsp;</td>
                </tr>';
        }

        $headerHtml = '';
        if ($useHeaderBanner) {
            $headerHtml = '<div class="header-banner-wrapper"><img class="header-banner-img" src="' . $headerBannerDataUri . '" alt="Academic Header" /></div>';
        } else {
            $headerHtml = '
                <div class="header-fallback">
                    <div class="header-row">
                        <div class="header-left">
                            <div class="header-left-inner">
                                <div class="logo-cell">
                                    ' . ($allowHeaderImage && $logoDataUri !== ''
                                        ? '<img class="logo-img" src="' . $logoDataUri . '" alt="BSU Logo" />'
                                        : '<div class="logo-fallback">BSU</div>') . '
                                </div>
                                <div class="header-text-cell">
                                    <div class="header-title-main">Republic of the Philippines</div>
                                    <div class="header-title-sub">Benguet State University</div>
                                    <div class="header-title-office">OFFICE OF THE UNIVERSITY AND BOARD SECRETARY</div>
                                    <div class="header-title-place">La Trinidad, Benguet</div>
                                </div>
                            </div>
                        </div>
                        <div class="header-right">
                            <table class="doc-code-table">
                                <tr>
                                    <td class="doc-code-label">Document<br>Code:</td>
                                    <td class="doc-code-value">QF-OUBS-16</td>
                                    <td class="doc-code-label">Revision<br>Number:</td>
                                    <td class="doc-code-value doc-code-center">00</td>
                                </tr>
                                <tr>
                                    <td class="doc-code-label">Effectivity:</td>
                                    <td class="doc-code-value">July 17, 2024</td>
                                    <td class="doc-code-value" colspan="2"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>';
        }

        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Academic Council Summary - ' . $docNo . '</title>
    <style>
        @page {
            size: 8.5in 13in;
            margin: 5mm 7mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            font-size: 9pt;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            width: 100%;
            padding-bottom: 24mm;
            box-sizing: border-box;
        }
        .header-banner-wrapper {
            margin-bottom: 8pt;
        }
        .header-banner-img {
            width: 100%;
            height: auto;
            display: block;
            border: 1pt solid #000;
        }
        .header-fallback {
            border: 1pt solid #000;
            padding: 8pt;
            margin-bottom: 8pt;
        }
        .header-row {
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        .header-left {
            display: table-cell;
            width: 67%;
            vertical-align: middle;
            padding-right: 8pt;
        }
        .header-left-inner {
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        .logo-cell {
            display: table-cell;
            width: 74pt;
            vertical-align: middle;
            text-align: left;
        }
        .logo-img {
            width: 68pt;
            height: 68pt;
            display: block;
            object-fit: cover;
        }
        .logo-fallback {
            width: 64pt;
            height: 64pt;
            border: 1.5pt solid #2f5d26;
            border-radius: 50%;
            text-align: center;
            line-height: 64pt;
            font-size: 10pt;
            color: #2f5d26;
            font-weight: bold;
        }
        .header-text-cell {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        .header-right {
            display: table-cell;
            width: 33%;
            vertical-align: top;
        }
        .header-title-main {
            font-size: 9pt;
        }
        .header-title-sub {
            font-size: 19pt;
            color: #1a5c1e;
            font-family: "Old English Text MT", "Times New Roman", serif;
            font-weight: 700;
            margin-top: 2pt;
        }
        .header-title-office {
            font-size: 10.5pt;
            font-weight: bold;
            font-style: italic;
            letter-spacing: 0.2pt;
            margin-top: 2pt;
        }
        .header-title-place {
            font-size: 9pt;
            margin-top: 1pt;
        }
        .doc-code-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8.5pt;
        }
        .doc-code-table td {
            border: 1px solid #000;
            padding: 2pt 3pt;
            vertical-align: middle;
        }
        .doc-code-label {
            width: 34%;
            font-weight: normal;
            line-height: 1.1;
        }
        .doc-code-value {
            font-weight: normal;
            line-height: 1.1;
        }
        .doc-code-center {
            text-align: center;
        }
        .referendum-title {
            margin: 4pt 0 6pt;
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 12pt;
            margin: 0 0 6pt;
            font-style: italic;
            font-weight: bold;
        }
        .doc-line {
            border: 1px solid #000;
            background: #d7e6c1;
            padding: 5pt 6pt;
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 4pt;
            line-height: 1.25;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #000;
            padding: 4pt;
            vertical-align: middle;
        }
        th {
            background: #ecd282;
            text-align: center;
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .group-row td {
            background: #efefef;
            font-weight: bold;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.2pt;
        }
        .col-member { width: 48%; vertical-align: top; }
        .col-approve { width: 16%; text-align: center; }
        .col-disapprove { width: 16%; text-align: center; }
        .col-comments { width: 20%; vertical-align: top; }
        .member-name {
            font-weight: bold;
            margin-bottom: 1pt;
        }
        .member-pos {
            font-size: 8pt;
            color: #222;
            line-height: 1.2;
        }
        .signature-img {
            max-width: 90pt;
            max-height: 28pt;
            display: inline-block;
        }
        .empty-row {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 12pt 8pt;
        }
        .total-row td {
            font-weight: bold;
        }
        .total-label {
            text-align: right;
            font-style: italic;
            letter-spacing: 0.4pt;
            padding-right: 10pt;
        }
        .total-value {
            font-size: 16pt;
            font-style: italic;
            text-align: center;
            line-height: 1;
            padding: 6pt 4pt;
        }
        .uadmin-footer {
            position: fixed;
            left: 7mm;
            right: 7mm;
            bottom: 5mm;
            font-size: 7pt;
            line-height: 1.2;
        }
        .uadmin-footer-line {
            height: 0;
            border-top: 2pt solid #5fa54b;
            border-bottom: 0.6pt solid #b8d6ae;
            margin-bottom: 3pt;
        }
        .uadmin-footer-contact {
            width: 100%;
            display: table;
            font-size: 7pt;
            font-style: italic;
            font-weight: bold;
        }
        .uadmin-footer-left {
            display: table-cell;
            width: 50%;
            text-align: left;
        }
        .uadmin-footer-right {
            display: table-cell;
            width: 50%;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        ' . $headerHtml . '
        <div class="referendum-title">REFERENDUM <span class="subtitle">(Academic Council)</span></div>
        <div class="doc-line">' . ($docNo !== '' ? $docNo . ': ' : '') . $docTitle . '</div>
        <table>
            <thead>
                <tr>
                    <th class="col-member">Members</th>
                    <th class="col-approve">Approved</th>
                    <th class="col-disapprove">Disapproved</th>
                    <th class="col-comments">Comments</th>
                </tr>
            </thead>
            <tbody>
                ' . $rowsHtml . '
            </tbody>
        </table>

        <div class="uadmin-footer">
            <div class="uadmin-footer-line"></div>
            <div class="uadmin-footer-contact">
                <div class="uadmin-footer-left">
                    Telephone No. +63-74-422-2009<br>
                    E-mail Address: oubs@bsu.edu.ph
                </div>
                <div class="uadmin-footer-right">
                    Website: www.bsu.edu.ph<br>
                    FB Page: OUBS BSU
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
    }

    private function loadInstitutionLogoDataUri(): string
    {
        $paths = [
            ROOTPATH . '..' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'logo_bsu.png',
            FCPATH . 'logo_bsu.png',
            ROOTPATH . 'public' . DIRECTORY_SEPARATOR . 'logo_bsu.png',
            ROOTPATH . '..' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'bsu_official_logo.png',
            FCPATH . 'bsu_official_logo.png',
            ROOTPATH . 'public' . DIRECTORY_SEPARATOR . 'bsu_official_logo.png',
        ];

        foreach ($paths as $path) {
            if (!file_exists($path) || !is_file($path)) {
                continue;
            }

            $binary = @file_get_contents($path);
            if ($binary === false || $binary === '') {
                continue;
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode($binary);
        }

        return '';
    }

    private function generateBorSummaryPDF(array $document, array $actions, bool $allowSignatureImage = true, bool $allowHeaderImage = true): string
    {
        $docNo = htmlspecialchars((string) ($document['document_number'] ?? ''));
        $docTitle = htmlspecialchars((string) ($document['title'] ?? ''));
        $dateCreated = !empty($document['created_at']) ? date('F j, Y', strtotime($document['created_at'])) : '';
        $headerBannerDataUri = $this->loadHeaderBannerDataUri('bor');
        $useHeaderBanner = $allowHeaderImage && $headerBannerDataUri !== '';

        $remarksLabel = 'Remarks';
        $approvedCount = 0;
        foreach ($actions as $action) {
            if (($action['action'] ?? '') === 'approve') {
                $approvedCount++;
            }
        }

        $rowsHtml = '';
        if (empty($actions)) {
            $rowsHtml = '<tr><td colspan="6" class="empty-row">No responses recorded yet.</td></tr>';
        } else {
            foreach ($actions as $index => $action) {
                $actionValue = (string) ($action['action'] ?? '');
                $actionLabel = $actionValue === 'approve' ? 'APPROVED' : 'DISAPPROVED';
                $actionClass = $actionValue === 'approve' ? 'approve' : 'disapprove';
                $dateSigned = !empty($action['date_signed']) ? date('M j, Y', strtotime($action['date_signed'])) : '-';
                $name = htmlspecialchars((string) ($action['full_name'] ?? ''));
                $position = htmlspecialchars((string) ($action['position'] ?? ''));
                $remarks = htmlspecialchars((string) ($action['remarks'] ?? ''));

                $rowsHtml .= '
                <tr>
                    <td class="col-member">
                        <div class="member-name">' . $name . '</div>
                        <div class="member-pos">' . $position . '</div>
                    </td>
                    <td class="col-action"><span class="badge ' . $actionClass . '">' . $actionLabel . '</span></td>
                    <td class="col-remarks">' . ($remarks !== '' ? $remarks : '-') . '</td>
                    <td class="col-date">' . htmlspecialchars($dateSigned) . '</td>
                </tr>';
            }
        }

        $headerHtml = '';
        if ($useHeaderBanner) {
            $headerHtml = '<div class="header-banner-wrapper"><img class="header-banner-img" src="' . $headerBannerDataUri . '" alt="BSU Header" /></div>';
        } else {
            $headerHtml = '
            <div class="header-fallback">
                <div class="header-title-main">Republic of the Philippines</div>
                <div class="header-title-sub">Benguet State University</div>
                <div class="header-title-office">OFFICE OF THE UNIVERSITY AND BOARD SECRETARY</div>
                <div class="header-title-place">La Trinidad, Benguet</div>
            </div>';
        }

        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>BOR Summary - ' . $docNo . '</title>
    <style>
        @page {
            size: 8.5in 13in;
            margin: 5mm 7mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            font-size: 9pt;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            width: 100%;
            padding-bottom: 34mm;
            box-sizing: border-box;
        }
        .header-banner-wrapper {
            margin-bottom: 8pt;
        }
        .header-banner-img {
            width: 100%;
            height: auto;
            display: block;
            border: 1pt solid #000;
        }
        .header-fallback {
            border: 1pt solid #000;
            text-align: center;
            padding: 10pt 8pt;
            margin-bottom: 8pt;
        }
        .header-title-main {
            font-size: 9pt;
        }
        .header-title-sub {
            font-size: 17pt;
            color: #1a5c1e;
            font-family: "Times New Roman", serif;
            font-weight: bold;
            margin-top: 2pt;
        }
        .header-title-office {
            font-size: 9pt;
            font-weight: bold;
            margin-top: 2pt;
        }
        .header-title-place {
            font-size: 9pt;
            margin-top: 1pt;
        }
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 16pt;
            margin: 6pt 0 2pt;
            letter-spacing: 0.4pt;
            text-transform: uppercase;
        }
        .sub-title {
            text-align: center;
            font-size: 10pt;
            margin-bottom: 1pt;
            font-weight: bold;
            letter-spacing: 0.8pt;
            text-transform: uppercase;
        }
        .on-doc-inline {
            display: inline;
            margin-left: 6pt;
            font-weight: bold;
            font-style: italic;
        }
        .rule {
            border-top: 1.2pt solid #000;
            margin: 3pt 0 5pt;
        }
        .referendum-note {
            font-size: 10pt;
            line-height: 1.35;
            margin: 4pt 0 6pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #000;
            padding: 4pt 4pt;
            vertical-align: top;
        }
        th {
            background: #f3f3f3;
            text-align: left;
            font-size: 8pt;
        }
        .col-member { width: 38%; }
        .col-action { width: 20%; text-align: center; }
        .col-remarks { width: 26%; }
        .col-date { width: 16%; }
        .member-name {
            font-weight: bold;
            margin-bottom: 2pt;
        }
        .member-pos {
            font-size: 7.5pt;
            color: #333;
        }
        .badge {
            display: inline-block;
            border: 1px solid;
            border-radius: 999px;
            font-size: 7pt;
            padding: 1pt 5pt;
            font-weight: bold;
        }
        .badge.approve {
            border-color: #1f7a3a;
            color: #1f7a3a;
            background: #ebf8ef;
        }
        .badge.disapprove {
            border-color: #b42318;
            color: #b42318;
            background: #fff1f0;
        }
        .empty-row {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 12pt 8pt;
        }
        .certification {
            margin-top: 8pt;
            margin-bottom: 5pt;
            font-size: 9pt;
        }
        .cert-label {
            margin-bottom: 14pt;
        }
        .cert-name {
            font-weight: bold;
            font-size: 10pt;
            letter-spacing: 0.3pt;
            text-transform: uppercase;
        }
        .cert-title {
            font-size: 9pt;
        }
        .footer {
            position: fixed;
            left: 7mm;
            right: 7mm;
            bottom: 5mm;
            font-size: 6.8pt;
            line-height: 1.2;
            color: #222;
        }
        .footer-notice {
            border: 1pt solid #000;
            padding: 4pt;
            text-align: justify;
            margin-bottom: 4pt;
        }
        .footer-rule {
            border-top: 1pt solid #7cb574;
            margin: 3pt 0 4pt;
        }
        .footer-contact {
            width: 100%;
        }
        .footer-left {
            float: left;
            width: 50%;
            font-style: italic;
        }
        .footer-right {
            float: right;
            width: 50%;
            text-align: right;
            font-style: italic;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        ' . $headerHtml . '
        <div class="title">SUMMARY OF ACTIONS TO REFERENDUM</div>
        <div class="rule"></div>
        <div class="sub-title">ACTIONS OF THE MEMBERS OF THE BOARD OF REGENTS OF BENGUET STATE UNIVERSITY ON <span class="on-doc-inline">' . ($docNo !== '' ? $docNo : '________________') . ' ' . ($docTitle !== '' ? $docTitle : '________________') . '</span></div>
        <div class="referendum-note">The referendum was routed on <u>' . htmlspecialchars($dateCreated !== '' ? $dateCreated : '____________') . '</u>; <u>' . htmlspecialchars((string) $approvedCount) . '</u> <strong>members</strong> of the Board approved the referendum. The details are summarized in the table below.</div>

        <table>
            <thead>
                <tr>
                    <th class="col-member">Member</th>
                    <th class="col-action">Action</th>
                    <th class="col-remarks">' . $remarksLabel . '</th>
                    <th class="col-date">Date Signed</th>
                </tr>
            </thead>
            <tbody>
                ' . $rowsHtml . '
            </tbody>
        </table>

        <div class="certification">
            <div class="cert-label">Certified true and correct:</div>
            <div class="cert-name">LYNN JUAN TALKASEN</div>
            <div class="cert-title">University and Board Secretary</div>
        </div>

        <div class="footer">
            <div class="footer-notice">
                The only controlled copy of this document is the original signed copy maintained in the Office of the University/Board Secretary.
                The reader must ensure that any other copy of a controlled document is current and complete prior to use. The user should secure
                the latest revision of this document from the Office of the University and Board Secretary. A photocopy of this document is
                UNCONTROLLED when scanned, photocopied and used by an unintended recipient.
            </div>
            <div class="footer-rule"></div>
            <div class="footer-contact clearfix">
                <div class="footer-left">
                    Telephone No. +63-74-422-2009<br>
                    El Address: oubs@bsu.edu.ph
                </div>
                <div class="footer-right">
                    Website: www.bsu.edu.ph<br>
                    FB Page: OUBS BSU
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
    }

    private function loadHeaderBannerDataUri(?string $recipientType = null): string
    {
        $ds = DIRECTORY_SEPARATOR;
        $rootCandidates = array_values(array_unique([
            rtrim(ROOTPATH, '\\/'),
            rtrim(dirname(ROOTPATH), '\\/'),
            rtrim(FCPATH, '\\/'),
            rtrim(dirname(FCPATH), '\\/'),
        ]));

        $paths = [];

        foreach ($rootCandidates as $root) {
            if ($recipientType === 'uac') {
                $paths[] = $root . $ds . 'frontend' . $ds . 'src' . $ds . 'assets' . $ds . 'acad_header.png';
                $paths[] = $root . $ds . 'frontend' . $ds . 'src' . $ds . 'assets' . $ds . 'acad_header.jpg';
                $paths[] = $root . $ds . 'frontend' . $ds . 'src' . $ds . 'assets' . $ds . 'acad_header.jpeg';
                $paths[] = $root . $ds . 'public' . $ds . 'acad_header.png';
                $paths[] = $root . $ds . 'public' . $ds . 'acad_header.jpg';
                $paths[] = $root . $ds . 'public' . $ds . 'acad_header.jpeg';
            }

            if ($recipientType === 'uadmin') {
                $paths[] = $root . $ds . 'frontend' . $ds . 'src' . $ds . 'assets' . $ds . 'uadmin_header.png';
                $paths[] = $root . $ds . 'frontend' . $ds . 'src' . $ds . 'assets' . $ds . 'uadmin_header.jpg';
                $paths[] = $root . $ds . 'frontend' . $ds . 'src' . $ds . 'assets' . $ds . 'uadmin_header.jpeg';
                $paths[] = $root . $ds . 'public' . $ds . 'uadmin_header.png';
                $paths[] = $root . $ds . 'public' . $ds . 'uadmin_header.jpg';
                $paths[] = $root . $ds . 'public' . $ds . 'uadmin_header.jpeg';
            }

            $paths[] = $root . $ds . 'frontend' . $ds . 'src' . $ds . 'assets' . $ds . 'reply-slip_header.png';
            $paths[] = $root . $ds . 'frontend' . $ds . 'src' . $ds . 'assets' . $ds . 'reply_slip_header.png';
            $paths[] = $root . $ds . 'frontend' . $ds . 'src' . $ds . 'assets' . $ds . 'reply-slip_header.jpg';
            $paths[] = $root . $ds . 'frontend' . $ds . 'src' . $ds . 'assets' . $ds . 'reply-slip_header.jpeg';
            $paths[] = $root . $ds . 'public' . $ds . 'reply-slip_header.png';
            $paths[] = $root . $ds . 'public' . $ds . 'reply_slip_header.png';
            $paths[] = $root . $ds . 'public' . $ds . 'reply-slip_header.jpg';
            $paths[] = $root . $ds . 'public' . $ds . 'reply-slip_header.jpeg';
        }

        $paths = array_values(array_unique($paths));

        foreach ($paths as $path) {
            if (!file_exists($path) || !is_file($path)) {
                continue;
            }

            $binary = @file_get_contents($path);
            if ($binary === false || $binary === '') {
                continue;
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png';
            log_message('debug', 'Summary PDF header image loaded from: ' . $path);
            return 'data:' . $mime . ';base64,' . base64_encode($binary);
        }

        log_message('error', 'Summary PDF header image not found. Recipient type: ' . (string) $recipientType);
        return '';
    }

    private function prepareSignatureForPdf(string $signatureDataUri): string
    {
        $signatureDataUri = trim($signatureDataUri);
        if ($signatureDataUri === '') {
            return '';
        }

        if (stripos($signatureDataUri, 'data:image/') !== 0) {
            return $signatureDataUri;
        }

        $parts = explode(',', $signatureDataUri, 2);
        if (count($parts) !== 2) {
            return $signatureDataUri;
        }

        $meta = $parts[0];
        $encoded = $parts[1];

        if (stripos($meta, ';base64') === false) {
            return $signatureDataUri;
        }

        // Clean malformed payload variants (spaces/newlines in base64).
        $encoded = str_replace(' ', '+', trim($encoded));
        $encoded = preg_replace('/\s+/', '', $encoded) ?? $encoded;
        $binary = base64_decode($encoded, true);
        if ($binary === false || $binary === '') {
            return $signatureDataUri;
        }

        $imageInfo = @getimagesizefromstring($binary);
        if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
            return $signatureDataUri;
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];
        $maxWidth = 820;
        $maxHeight = 200;
        $maxBytes = 220000;

        if (!function_exists('imagecreatefromstring') || !function_exists('imagepng')) {
            return $signatureDataUri;
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return $signatureDataUri;
        }

        $scale = min($maxWidth / max(1, $width), $maxHeight / max(1, $height), 1);
        $targetWidth = max(1, (int) floor($width * $scale));
        $targetHeight = max(1, (int) floor($height * $scale));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($target === false) {
            imagedestroy($source);
            return $signatureDataUri;
        }

        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);

        $ok = imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $width,
            $height
        );

        imagedestroy($source);

        if (!$ok) {
            imagedestroy($target);
            return $signatureDataUri;
        }

        $resizedBinary = '';
        $written = false;

        // Force a safe output size for dompdf across varying upload qualities.
        for ($attempt = 0; $attempt < 6; $attempt++) {
            ob_start();
            $written = imagepng($target, null, 9);
            $candidate = ob_get_clean();

            if ($written && $candidate !== false && $candidate !== '') {
                $resizedBinary = $candidate;
                if (strlen($resizedBinary) <= $maxBytes) {
                    break;
                }
            }

            $currW = imagesx($target);
            $currH = imagesy($target);
            $nextW = max(1, (int) floor($currW * 0.85));
            $nextH = max(1, (int) floor($currH * 0.85));
            if ($nextW === $currW && $nextH === $currH) {
                break;
            }

            $next = imagecreatetruecolor($nextW, $nextH);
            if ($next === false) {
                break;
            }
            imagealphablending($next, false);
            imagesavealpha($next, true);
            $nextTransparent = imagecolorallocatealpha($next, 255, 255, 255, 127);
            imagefilledrectangle($next, 0, 0, $nextW, $nextH, $nextTransparent);
            imagecopyresampled($next, $target, 0, 0, 0, 0, $nextW, $nextH, $currW, $currH);
            imagedestroy($target);
            $target = $next;
        }

        imagedestroy($target);

        if (!$written || $resizedBinary === '') {
            return $signatureDataUri;
        }

        return 'data:image/png;base64,' . base64_encode($resizedBinary);
    }
}
