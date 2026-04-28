<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ReplySlipModel;
use App\Models\DocumentModel;
use App\Models\SummaryActionModel;
use App\Models\UserModel;
use App\Models\BORMemberModel;
use App\Models\NotificationModel;
use CodeIgniter\API\ResponseTrait;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReplySlipController extends BaseController
{
    use ResponseTrait;

    protected $replySlipModel;
    protected $documentModel;
    protected $summaryModel;
    protected $userModel;
    protected $borModel;
    protected $notificationModel;

    public function __construct()
    {
        $this->replySlipModel = new ReplySlipModel();
        $this->documentModel = new DocumentModel();
        $this->summaryModel = new SummaryActionModel();
        $this->userModel = new UserModel();
        $this->borModel = new BORMemberModel();
        $this->notificationModel = new NotificationModel();
    }

    public function create()
    {
        // Only recipients can create reply slips
        $user = $this->request->user;
        if ($user['user_type'] === 'oubs') {
            return $this->respond(['error' => 'Access denied'], 403);
        }

        $json = $this->request->getJSON(true);
        
        $validationRules = [
            'document_id' => 'required|numeric',
            'action' => 'required|in_list[approve,disapprove]',
            'remarks' => 'permit_empty|string',
            'signature_image' => 'permit_empty'
        ];

        if (!$this->validate($validationRules)) {
            return $this->respond(['errors' => $this->validator->getErrors()], 400);
        }

        // Check if document exists and is for this user's type
        $document = $this->documentModel->find($json['document_id']);
        if (!$document) {
            return $this->respond(['error' => 'Document not found'], 404);
        }

        if ($document['recipient_type'] !== $user['user_type']) {
            return $this->respond(['error' => 'Document not for your recipient type'], 403);
        }

        if (($document['recipient_type'] ?? '') === 'bor' && ($document['review_status'] ?? 'allowed') !== 'allowed') {
            return $this->respond(['error' => 'Document is not accessible'], 403);
        }

        if (((int) ($document['allow_replies'] ?? 1)) === 0) {
            return $this->respond(['error' => 'This document is view-only and does not accept replies.'], 403);
        }

        if ($this->isPastDeadline($document['reply_deadline_at'] ?? null)) {
            if (($document['status'] ?? '') !== 'closed') {
                $this->documentModel->update($document['id'], ['status' => 'closed']);
                
                // Create notification for recipients that document is closed
                try {
                    $recipientType = $document['recipient_type'] ?? '';
                    $recipientUsers = [];
                    
                    if ($recipientType === 'bor') {
                        $recipientUsers = $this->userModel
                            ->select('users.id')
                            ->join('bor_members', 'bor_members.user_id = users.id')
                            ->where('users.is_active', 1)
                            ->findAll();
                    } elseif (in_array($recipientType, ['uac', 'uadmin'], true)) {
                        $recipientUsers = $this->userModel
                            ->select('id')
                            ->where('user_type', $recipientType)
                            ->where('is_active', 1)
                            ->findAll();
                    }
                    
                    foreach ($recipientUsers as $user) {
                        $this->notificationModel->createNotification(
                            $user['id'],
                            'document_closed',
                            'Document Closed',
                            'The document "' . ($document['title'] ?? 'Document') . '" has been closed.',
                            [
                                'document_id' => $document['id'],
                                'document_title' => $document['title'] ?? '',
                                'document_number' => $document['document_number'] ?? '',
                            ],
                            '/recipient/documents/' . $document['id']
                        );
                    }
                } catch (\Exception $e) {
                    log_message('error', 'Failed to create document closed notification: ' . $e->getMessage());
                }
            }

            return $this->respond([
                'error' => 'Reply period has ended. Document is closed.'
            ], 403);
        }

        if (($document['status'] ?? '') === 'closed' && !$this->isPastDeadline($document['reply_deadline_at'] ?? null)) {
            $this->documentModel->update($document['id'], ['status' => 'pending']);
            $document['status'] = 'pending';
        }

        if (($document['status'] ?? '') === 'closed') {
            return $this->respond([
                'error' => 'Document is closed and no longer accepting replies.'
            ], 403);
        }

        // Check if user already submitted a reply
        $existingReply = $this->replySlipModel
            ->where('document_id', $json['document_id'])
            ->where('user_id', $user['user_id'])
            ->first();

        if ($existingReply) {
            return $this->respond(['error' => 'You have already submitted a reply for this document'], 400);
        }

        $requestSignature = trim((string) ($json['signature_image'] ?? ''));
        $userRecord = $this->userModel->find((int) $user['user_id']);
        $storedUserSignature = trim((string) ($userRecord['signature_image'] ?? ''));
        $effectiveSignature = $requestSignature !== '' ? $requestSignature : $storedUserSignature;
        if ($effectiveSignature === '') {
            return $this->respond(['error' => 'E-signature image is required. Please upload your e-signature first.'], 400);
        }

        $data = [
            'document_id' => $json['document_id'],
            'user_id' => $user['user_id'],
            'action' => $json['action'],
            'remarks' => isset($json['remarks']) ? trim((string) $json['remarks']) : null,
            'signature_image' => $this->prepareSignatureForStorage($effectiveSignature),
            'date_signed' => date('Y-m-d H:i:s')
        ];

        if ($this->replySlipModel->insert($data)) {
            $replyId = $this->replySlipModel->getInsertID();
            
            // Record in summary of actions
            $this->summaryModel->insert([
                'document_id' => $json['document_id'],
                'user_id' => $user['user_id'],
                'full_name' => $user['full_name'],
                'position' => $user['position'],
                'action' => $json['action'],
                'remarks' => isset($json['remarks']) ? trim((string) $json['remarks']) : null,
                'date_signed' => date('Y-m-d H:i:s')
            ]);

            // Create notification for reply success
            try {
                $this->notificationModel->createNotification(
                    $user['user_id'],
                    'reply_success',
                    'Reply Submitted',
                    'Your reply has been successfully submitted for: ' . ($document['title'] ?? 'Document'),
                    [
                        'document_id' => $json['document_id'],
                        'document_title' => $document['title'] ?? '',
                        'reply_id' => $replyId,
                    ],
                    '/recipient/my-replies'
                );
            } catch (\Exception $e) {
                log_message('error', 'Failed to create reply success notification: ' . $e->getMessage());
            }

            // Update document status if all recipients have replied
            $this->updateDocumentStatus($json['document_id'], $user['user_type']);

            return $this->respond([
                'status' => 'success',
                'message' => 'Reply slip submitted successfully',
                'id' => $replyId
            ], 201);
        }

        return $this->respond(['error' => 'Failed to submit reply slip'], 500);
    }

    public function byDocument($documentId)
    {
        $user = $this->request->user;
        $document = $this->documentModel->find($documentId);

        if (!$document) {
            return $this->respond(['error' => 'Document not found'], 404);
        }

        // Check access
        if ($user['user_type'] !== 'oubs') {
            if ($document['recipient_type'] !== $user['user_type']) {
                return $this->respond(['error' => 'Access denied'], 403);
            }
            if (($document['recipient_type'] ?? '') === 'bor' && ($document['review_status'] ?? 'allowed') !== 'allowed') {
                return $this->respond(['error' => 'Document is not accessible'], 403);
            }
        }

        $replySlips = $this->replySlipModel
            ->select('reply_slips.*, users.full_name, users.position')
            ->join('users', 'users.id = reply_slips.user_id')
            ->where('document_id', $documentId)
            ->orderBy('date_signed', 'ASC')
            ->findAll();

        return $this->respond([
            'status' => 'success',
            'data' => $replySlips
        ]);
    }

    public function myReplies()
    {
        $user = $this->request->user;
        
        $replies = $this->replySlipModel
            ->select('reply_slips.*, documents.document_number, documents.title, documents.recipient_type')
            ->join('documents', 'documents.id = reply_slips.document_id')
            ->where('reply_slips.user_id', $user['user_id'])
            ->orderBy('reply_slips.created_at', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => 'success',
            'data' => $replies
        ]);
    }

    public function download($id)
    {
        $replySlip = $this->replySlipModel
            ->select('reply_slips.*, documents.document_number, documents.title, documents.recipient_type, users.full_name, users.position')
            ->join('documents', 'documents.id = reply_slips.document_id')
            ->join('users', 'users.id = reply_slips.user_id')
            ->find($id);

        if (!$replySlip) {
            return $this->respond(['error' => 'Reply slip not found'], 404);
        }

        // Check access
        $user = $this->request->user;
        if ($user['user_type'] !== 'oubs' && $replySlip['user_id'] !== $user['user_id']) {
            return $this->respond(['error' => 'Access denied'], 403);
        }

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $pdfOutput = null;

        $recipientType = $replySlip['recipient_type'] ?? '';
        $isBorRecipient = $recipientType === 'bor';
        if (!$isBorRecipient) {
            return $this->respond([
                'error' => 'PDF copy is only available for Board of Regents (BOR) reply slips.'
            ], 400);
        }

        try {
            $html = $this->generateReplySlipPDF($replySlip, true, true, true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfOutput = $dompdf->output();
        } catch (\Throwable $error) {
            log_message('error', 'Reply slip PDF render failed (full). Retrying with signature but without header/logo images. Error: ' . $error->getMessage());
            try {
                // Keep signature rendering, disable other images first.
                $html = $this->generateReplySlipPDF($replySlip, true, false, false);
                $dompdf = new Dompdf($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $pdfOutput = $dompdf->output();
            } catch (\Throwable $errorWithSignatureOnly) {
                log_message('error', 'Reply slip PDF render failed (signature only). Retrying without all images. Error: ' . $errorWithSignatureOnly->getMessage());
                $html = $this->generateReplySlipPDF($replySlip, false, false, false);
                $dompdf = new Dompdf($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $pdfOutput = $dompdf->output();
            }
        }

        $fileName = 'reply-slip-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) $replySlip['document_number']) . '.pdf';

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->setBody($pdfOutput);
    }

    private function prepareSignatureForStorage(string $signatureDataUri): string
    {
        $signatureDataUri = trim($signatureDataUri);
        if ($signatureDataUri === '') {
            return $signatureDataUri;
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

        $binary = base64_decode($encoded, true);
        if ($binary === false || $binary === '') {
            return $signatureDataUri;
        }

        $imageInfo = @getimagesizefromstring($binary);
        if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
            return $signatureDataUri;
        }

        // Enforce a strict signature canvas limit so all uploaded qualities remain renderable.
        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];
        $maxWidth = 820;
        $maxHeight = 200;
        $maxOutputBytes = 450000;

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

        $pngBinary = '';
        $written = false;

        // Iteratively reduce dimensions until output is within a safe size budget.
        for ($attempt = 0; $attempt < 5; $attempt++) {
            ob_start();
            $written = imagepng($target, null, 9);
            $candidate = ob_get_clean();

            if ($written && $candidate !== false && $candidate !== '') {
                $pngBinary = $candidate;
                if (strlen($pngBinary) <= $maxOutputBytes) {
                    break;
                }
            }

            $nextWidth = max(1, (int) floor(imagesx($target) * 0.85));
            $nextHeight = max(1, (int) floor(imagesy($target) * 0.85));
            if ($nextWidth === imagesx($target) && $nextHeight === imagesy($target)) {
                break;
            }

            $next = imagecreatetruecolor($nextWidth, $nextHeight);
            if ($next === false) {
                break;
            }
            imagealphablending($next, false);
            imagesavealpha($next, true);
            $nextTransparent = imagecolorallocatealpha($next, 255, 255, 255, 127);
            imagefilledrectangle($next, 0, 0, $nextWidth, $nextHeight, $nextTransparent);
            imagecopyresampled($next, $target, 0, 0, 0, 0, $nextWidth, $nextHeight, imagesx($target), imagesy($target));
            imagedestroy($target);
            $target = $next;
        }

        imagedestroy($target);

        if (!$written || $pngBinary === '') {
            return $signatureDataUri;
        }

        return 'data:image/png;base64,' . base64_encode($pngBinary);
    }

    private function isPastDeadline(?string $deadline): bool
    {
        $deadline = trim((string) $deadline);
        if ($deadline === '') {
            return false;
        }

        $timestamp = strtotime($deadline);
        if ($timestamp === false) {
            return false;
        }

        return time() > $timestamp;
    }

    private function updateDocumentStatus($documentId, $recipientType)
    {
        $document = $this->documentModel->find($documentId);
        if ($document && ($document['status'] ?? '') === 'closed') {
            return;
        }

        $totalRecipients = $this->getRecipientCount($recipientType);
        
        $totalReplies = $this->replySlipModel
            ->where('document_id', $documentId)
            ->countAllResults();

        if ($totalRecipients > 0 && $totalReplies >= $totalRecipients) {
            $this->documentModel->update($documentId, ['status' => 'completed']);
            
            // Create notification for OUBS about document completion
            try {
                $oubsUsers = $this->userModel
                    ->select('id')
                    ->where('user_type', 'oubs')
                    ->where('is_active', 1)
                    ->findAll();
                
                foreach ($oubsUsers as $user) {
                    $this->notificationModel->createNotification(
                        $user['id'],
                        'document_completed',
                        'Document Completed',
                        'Document "' . ($document['title'] ?? 'Document') . '" has been completed and closed.',
                        [
                            'document_id' => $documentId,
                            'document_title' => $document['title'] ?? '',
                            'document_number' => $document['document_number'] ?? '',
                        ],
                        '/oubs/documents'
                    );
                }
            } catch (\Exception $e) {
                log_message('error', 'Failed to create document completed notification: ' . $e->getMessage());
            }
        }
    }

    private function getRecipientCount($recipientType)
    {
        if (!in_array($recipientType, ['bor', 'uac', 'uadmin'], true)) {
            return 0;
        }

        if ($recipientType === 'bor') {
            return $this->borModel
                ->join('users', 'users.id = bor_members.user_id')
                ->where('users.is_active', 1)
                ->countAllResults();
        }

        return $this->userModel
            ->where('user_type', $recipientType)
            ->where('is_active', 1)
            ->countAllResults();
    }

    private function generateReplySlipPDF($replySlip, bool $allowSignatureImage = true, bool $allowLogoImage = true, bool $allowHeaderImage = true)
    {
        $dateSigned = date('F j, Y', strtotime($replySlip['date_signed']));
        $isApproved = $replySlip['action'] === 'approve';
        $remarksRaw = trim((string) ($replySlip['remarks'] ?? ''));
        $remarks = $remarksRaw !== '' ? nl2br(htmlspecialchars($remarksRaw)) : '&nbsp;';
        $docNo = htmlspecialchars((string) ($replySlip['document_number'] ?? ''));
        $title = htmlspecialchars((string) ($replySlip['title'] ?? ''));
        $fullName = htmlspecialchars((string) ($replySlip['full_name'] ?? ''));
        $position = htmlspecialchars((string) ($replySlip['position'] ?? ''));
        
        // Format recipient name exactly as in PDF
        $recipientNameRaw = trim((string) ($replySlip['full_name'] ?? ''));
        $recipientNameUpper = strtoupper($recipientNameRaw);
        if ($recipientNameUpper !== '' && stripos($recipientNameUpper, 'HON.') !== 0) {
            $recipientNameUpper = 'HON. ' . $recipientNameUpper;
        }
        $recipientNameLine = htmlspecialchars($recipientNameUpper);
        
        $canRenderSignature = $allowSignatureImage;
        $canRenderLogo = $allowLogoImage;
        
        $logoDataUri = $this->loadInstitutionLogoDataUri();
        $logoHtml = ($canRenderLogo && $logoDataUri !== '')
            ? '<img class="logo-img" src="' . $logoDataUri . '" alt="BSU Logo" />'
            : '<div class="logo-fallback">BSU</div>';
        
        // Load header banner image
        $headerBannerDataUri = $this->loadHeaderBannerDataUri();
        $useHeaderBanner = ($allowHeaderImage && $headerBannerDataUri !== '');

        // Signature rendering
        $signatureImage = ($canRenderSignature && !empty($replySlip['signature_image']))
            ? '<img class="signature-img" src="' . htmlspecialchars($replySlip['signature_image']) . '" alt="Signature" />'
            : '<div class="signature-placeholder"></div>';
        
        $signatureNote = $canRenderSignature
            ? ''
            : '<div class="signature-note">(Signature image unavailable on this server configuration)</div>';

        // Checkbox marks - using HTML entity for checked/unchecked boxes
        // Use ASCII-safe markers so Dompdf always renders correctly.
        $approvedMark = $isApproved ? '[X]' : '[ ]';
        $disapprovedMark = $isApproved ? '[ ]' : '[X]';

        $html = '
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Reply Slip - ' . $docNo . '</title>
  <style>
    @page { 
        size: A4 portrait; 
        margin: 10mm 15mm 10mm 15mm;
    }
    
    body { 
        font-family: Arial, Helvetica, sans-serif; 
        color: #000; 
        font-size: 11pt; 
        margin: 0; 
        padding: 0;
        line-height: 1.4;
    }
    
    .document-wrapper {
        width: 100%;
        max-width: 180mm;
        margin: 0 auto;
    }
    
    /* Header Banner Image (when available) */
    .header-banner-wrapper {
        width: 100%;
        margin-bottom: 8pt;
        padding: 0;
    }
    
    .header-banner-img {
        width: 100%;
        height: auto;
        display: block;
        border: 1.5pt solid #000;
    }
    
    /* Header Section (fallback when no banner image) */
    .header-wrapper {
        border: 1.5pt solid #000;
        padding: 8pt;
        margin-bottom: 8pt;
    }
    
    .header-top-row {
        display: table;
        width: 100%;
        margin-bottom: 6pt;
    }
    
    .header-left {
        display: table-cell;
        width: 65%;
        vertical-align: top;
    }
    
    .header-right {
        display: table-cell;
        width: 35%;
        vertical-align: top;
        padding-left: 8pt;
    }
    
    .logo-and-text {
        display: table;
        width: 100%;
    }
    
    .logo-cell {
        display: table-cell;
        width: 70pt;
        vertical-align: middle;
        padding-right: 8pt;
    }
    
    .logo-img {
        width: 65pt;
        height: 65pt;
        display: block;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .logo-fallback {
        width: 65pt;
        height: 65pt;
        border: 2pt solid #2f5d26;
        border-radius: 50%;
        background: #f5f5f5;
        text-align: center;
        line-height: 65pt;
        font-weight: bold;
        color: #2f5d26;
        font-size: 12pt;
    }
    
    .header-text-cell {
        display: table-cell;
        vertical-align: middle;
        text-align: center;
    }
    
    .republic {
        font-size: 9pt;
        margin: 0 0 2pt 0;
    }
    
    .university-name {
        font-family: "Old English Text MT", "Times New Roman", serif;
        font-size: 16pt;
        font-weight: normal;
        color: #1a5c1e;
        margin: 0;
        line-height: 1.1;
    }
    
    .office-name {
        font-size: 8pt;
        font-weight: bold;
        letter-spacing: 0.3pt;
        margin: 2pt 0 0 0;
        font-style: italic;
    }
    
    .location {
        font-size: 9pt;
        margin: 2pt 0 0 0;
    }
    
    .contact-footer-top {
        font-size: 8pt;
        margin-top: 4pt;
    }
    
    /* Document Code Table */
    .doc-code-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8pt;
    }
    
    .doc-code-table td {
        border: 0.5pt solid #000;
        padding: 2pt 4pt;
        text-align: left;
    }
    
    .doc-code-label {
        font-weight: normal;
    }
    
    .doc-code-value {
        font-weight: normal;
    }
    
    .revision-label {
        text-align: center;
    }
    
    .revision-number {
        text-align: center;
        font-weight: bold;
    }
    
    /* Green decorative line */
    .green-line {
        height: 2pt;
        background: linear-gradient(to right, #b8d6ae 0%, #7cb574 50%, #b8d6ae 100%);
        margin: 6pt 0;
    }
    
    /* Reply Slip Title */
    .reply-slip-title {
        text-align: center;
        font-size: 18pt;
        font-weight: bold;
        margin: 6pt 0;
        letter-spacing: 0.5pt;
    }
    
    /* Content Section */
    .content-section {
        margin: 12pt 0;
        padding: 0 8pt;
    }
    
    .memo-line {
        margin-bottom: 12pt;
        font-size: 10.5pt;
    }

    .memo-line-row {
        display: table;
        width: 100%;
        table-layout: auto;
        font-size: 12pt;
        font-weight: 700;
    }

    .memo-docno,
    .memo-colon,
    .memo-docname {
        display: table-cell;
        vertical-align: top;
    }

    .memo-docno {
        width: 1%;
        white-space: nowrap;
        font-weight: 700;
        padding-right: 6pt;
    }

    .memo-colon {
        width: 1%;
        text-align: center;
        white-space: nowrap;
        font-weight: 700;
        padding: 0 4pt;
    }

    .memo-docname {
        width: auto;
        white-space: normal;
        overflow: visible;
        text-overflow: clip;
        overflow-wrap: break-word;
        word-break: normal;
        font-weight: 700;
        padding-left: 6pt;
    }

    .memo-divider {
        border-bottom: 2pt solid #000;
        margin-top: 3pt;
    }
    
    .checkbox-section {
        margin: 14pt 0;
    }
    
    .checkbox-intro {
        margin-bottom: 8pt;
        font-size: 10.5pt;
    }
    
    .checkbox-option {
        margin: 6pt 0;
        font-size: 11pt;
        font-weight: bold;
    }
    
    .checkbox-box {
        display: inline-block;
        width: 14pt;
        font-size: 14pt;
        margin-right: 8pt;
    }
    
    .remarks-section {
        margin: 12pt 0;
        font-size: 10.5pt;
    }
    
    .remarks-label {
        display: inline-block;
        width: 60pt;
    }
    
    .remarks-underline {
        text-decoration: underline;
        font-style: italic;
    }
    
    .remarks-text {
        display: inline-block;
        border-bottom: 1pt solid #000;
        min-width: 380pt;
        min-height: 16pt;
        margin-left: 4pt;
    }
    
    /* Signature Section */
    .signature-area {
        margin-top: 20pt;
    }
    
    .signature-box {
        float: right;
        width: 45%;
        text-align: left;
        margin-bottom: 16pt;
    }
    
    .signature-img {
        max-width: 150pt;
        max-height: 60pt;
        margin-bottom: 4pt;
    }
    
    .signature-placeholder {
        width: 150pt;
        height: 50pt;
        border: 1pt dashed #ccc;
        background: #f9f9f9;
        margin-bottom: 4pt;
    }
    
    .signature-note {
        font-size: 8pt;
        color: #666;
        font-style: italic;
    }
    
    .signature-name {
        font-weight: bold;
        margin: 2pt 0;
        font-size: 11pt;
    }
    
    .signature-date {
        margin: 2pt 0;
        font-size: 10.5pt;
    }
    
    .by-section {
        clear: both;
        margin-top: 16pt;
        padding-left: 8pt;
    }
    
    .by-label {
        margin-bottom: 6pt;
        font-size: 10.5pt;
    }
    
    .chairperson-name {
        font-weight: bold;
        margin: 1pt 0;
        font-size: 10.5pt;
    }
    
    .chairperson-title {
        margin: 1pt 0;
        font-size: 10pt;
    }
    
    /* Footer Section */
    .footer-wrapper {
        margin-top: 20pt;
        padding-top: 8pt;
        border-top: 1pt dotted #999;
        font-size: 7.5pt;
        text-align: justify;
        line-height: 1.3;
    }
    
    .footer-notice {
        padding: 6pt;
        border: 1pt solid #000;
        margin-bottom: 6pt;
    }
    
    .footer-green-line {
        height: 1.5pt;
        background: linear-gradient(to right, #b8d6ae 0%, #7cb574 50%, #b8d6ae 100%);
        margin: 4pt 0;
    }
    
    .contact-info {
        display: table;
        width: 100%;
        font-size: 8pt;
        font-style: italic;
    }
    
    .contact-left {
        display: table-cell;
        width: 50%;
        text-align: left;
    }
    
    .contact-right {
        display: table-cell;
        width: 50%;
        text-align: right;
    }
    
    .clearfix::after {
        content: "";
        display: table;
        clear: both;
    }
  </style>
</head>
<body>
  <div class="document-wrapper">
    ';
        
        // Use header banner if available, otherwise use text-based header
        if ($useHeaderBanner) {
            $html .= '
    <!-- Header Banner -->
    <div class="header-banner-wrapper">
      <img class="header-banner-img" src="' . $headerBannerDataUri . '" alt="Reply Slip Header" />
    </div>';
        } else {
            $html .= '
    <!-- Header (Text-based fallback) -->
    <div class="header-wrapper">
      <div class="header-top-row">
        <div class="header-left">
          <div class="logo-and-text">
            <div class="logo-cell">
              ' . $logoHtml . '
            </div>
            <div class="header-text-cell">
              <p class="republic">Republic of the Philippines</p>
              <p class="university-name">Benguet State University</p>
              <p class="office-name">OFFICE OF THE UNIVERSITY AND BOARD SECRETARY</p>
              <p class="location">La Trinidad, Benguet</p>
            </div>
          </div>
        </div>
        
        <div class="header-right">
          <table class="doc-code-table">
            <tr>
              <td class="doc-code-label">Document Code:</td>
              <td class="doc-code-value">QF-OUBS-07</td>
              <td class="revision-label">Revision<br>Number:</td>
              <td class="revision-number">0</td>
            </tr>
            <tr>
              <td class="doc-code-label">Effectivity Date:</td>
              <td class="doc-code-value">July 17, 2024</td>
              <td></td>
              <td></td>
            </tr>
          </table>
        </div>
      </div>
      
      <div class="contact-footer-top">
        Telephone No. +63-74-422-2009&nbsp;&nbsp;&nbsp;&nbsp;Website: www.bsu.edu.ph<br>
        El Address: oubs@bsu.edu.ph&nbsp;&nbsp;&nbsp;&nbsp;FB Page: OUBS BSU
      </div>
    </div>';
        }
        
        $html .= '
    <!-- Title Section -->
    <div class="green-line"></div>
    <div class="reply-slip-title">REPLY SLIP</div>
    <div class="green-line"></div>
    
    <!-- Content -->
    <div class="content-section">
      <div class="memo-line">
        <div class="memo-line-row">
          <span class="memo-docno">' . $docNo . '</span>
          <span class="memo-colon">:</span>
          <span class="memo-docname">' . $title . '</span>
        </div>
        <div class="memo-divider"></div>
      </div>
      
      <div class="checkbox-section">
        <div class="checkbox-intro">Please check the appropriate box below:</div>
        <div class="checkbox-option">
          <span class="checkbox-box">' . $approvedMark . '</span> APPROVED
        </div>
        <div class="checkbox-option">
          <span class="checkbox-box">' . $disapprovedMark . '</span> DISAPPROVED
        </div>
      </div>
      
      <div class="remarks-section">
        <span class="remarks-label">Remarks: <span class="remarks-underline">' . $remarks . '</span></span>
      </div>
      
      <!-- Signature Area -->
      <div class="signature-area clearfix">
        <div class="signature-box">
          ' . $signatureImage . '
          ' . $signatureNote . '
          <div class="signature-name">' . $recipientNameLine . '</div>
          <div class="signature-date">Date: ' . htmlspecialchars($dateSigned) . '</div>
        </div>
        
        <div class="by-section">
          <div class="by-label">By:</div>
          <div class="chairperson-name">HON. MYRNA Q. MALLARI</div>
          <div class="chairperson-title">Commissioner, CHED</div>
          <div class="chairperson-title">Chairperson</div>
          <div class="chairperson-title">Board of Regents</div>
          <div class="chairperson-title">Benguet State University</div>
        </div>
      </div>
    </div>
    
    <!-- Footer -->
    <div class="footer-wrapper">
      <div class="footer-notice">
        The only controlled copy of this document is the original signed copy maintained in the Office of the University/Board Secretary. 
        The reader must ensure that any other copy of a controlled document is current and complete prior to use. The user should secure 
        the latest revision of this document from the Office of the University and Board Secretary. A photocopy of this document is 
        UNCONTROLLED when scanned, photocopied and used by an unintended recipient.
      </div>
      
      <div class="footer-green-line"></div>
      
      <div class="contact-info">
        <div class="contact-left">
          Telephone No. +63-74-422-2009<br>
          El Address: oubs@bsu.edu.ph
        </div>
        <div class="contact-right">
          Website: www.bsu.edu.ph<br>
          FB Page: OUBS BSU
        </div>
      </div>
    </div>
    
  </div>
</body>
</html>';

        return $html;
    }


    private function loadInstitutionLogoDataUri(): string
    {
        // Primary paths - most likely locations
        $paths = [
            // Frontend assets (most common)
            ROOTPATH . '..' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'logo_bsu.png',
            // Public folder
            FCPATH . 'logo_bsu.png',
            ROOTPATH . 'public' . DIRECTORY_SEPARATOR . 'logo_bsu.png',
            // Alternative names
            ROOTPATH . '..' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'bsu_official_logo.png',
            FCPATH . 'bsu_official_logo.png',
            ROOTPATH . 'public' . DIRECTORY_SEPARATOR . 'bsu_official_logo.png',
        ];

        foreach ($paths as $path) {
            // Log the path being checked for debugging
            log_message('debug', 'Checking logo path: ' . $path);
            
            if (!file_exists($path) || !is_file($path)) {
                continue;
            }
            
            $binary = @file_get_contents($path);
            if ($binary === false || $binary === '') {
                log_message('warning', 'Logo file exists but could not read: ' . $path);
                continue;
            }
            
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png';
            
            log_message('info', 'Successfully loaded logo from: ' . $path);
            return 'data:' . $mime . ';base64,' . base64_encode($binary);
        }

        log_message('warning', 'No logo file found in any checked paths');
        return '';
    }

    private function loadHeaderBannerDataUri(): string
    {
        // Primary paths for header banner
        $paths = [
            // Frontend assets - exact path you provided
            ROOTPATH . '..' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'reply-slip_header.png',
            // Alternative naming
            ROOTPATH . '..' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'reply_slip_header.png',
            ROOTPATH . '..' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'reply-slip_header.jpg',
            ROOTPATH . '..' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'reply-slip_header.jpeg',
            // Public folder
            FCPATH . 'reply-slip_header.png',
            FCPATH . 'reply_slip_header.png',
            FCPATH . 'reply-slip_header.jpg',
            FCPATH . 'reply-slip_header.jpeg',
            ROOTPATH . 'public' . DIRECTORY_SEPARATOR . 'reply-slip_header.png',
            ROOTPATH . 'public' . DIRECTORY_SEPARATOR . 'reply_slip_header.png',
            ROOTPATH . 'public' . DIRECTORY_SEPARATOR . 'reply-slip_header.jpg',
            ROOTPATH . 'public' . DIRECTORY_SEPARATOR . 'reply-slip_header.jpeg',
        ];

        foreach ($paths as $path) {
            log_message('debug', 'Checking header banner path: ' . $path);
            
            if (!file_exists($path) || !is_file($path)) {
                continue;
            }
            
            $binary = @file_get_contents($path);
            if ($binary === false || $binary === '') {
                log_message('warning', 'Header banner file exists but could not read: ' . $path);
                continue;
            }
            
            // Detect actual MIME type from file content, not just extension
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = finfo_buffer($finfo, $binary);
            finfo_close($finfo);
            
            // Use detected MIME type, fallback to extension-based
            if ($detectedMime && strpos($detectedMime, 'image/') === 0) {
                $mime = $detectedMime;
            } else {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png';
            }
            
            log_message('info', 'Successfully loaded header banner from: ' . $path . ' (MIME: ' . $mime . ')');
            return 'data:' . $mime . ';base64,' . base64_encode($binary);
        }

        log_message('info', 'No header banner found, will use legacy text header');
        return '';
    }
}
