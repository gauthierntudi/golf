<?php
require_once 'config.php';
require_once 'db.php';
require_once '../vendor/autoload.php';

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

// Get invitee code from POST
$invitationCode = isset($_POST['code']) ? sanitizeInput($_POST['code']) : null;

if (!$invitationCode) {
    jsonResponse(['success' => false, 'message' => 'Missing invitation code'], 400);
}

// Get invitee data
$invitee = getInviteeByCode($invitationCode);

if (!$invitee) {
    jsonResponse(['success' => false, 'message' => 'Invalid invitation code'], 404);
}

// Check if the invitation is confirmed
if (!$invitee['confirmed']) {
    jsonResponse(['success' => false, 'message' => 'Please confirm your attendance first'], 400);
}

// Generate the invitation
try {
    // Create directory for invitations if it doesn't exist
    $invitationsDir = __DIR__ . '/../assets/invitations';
    if (!file_exists($invitationsDir)) {
        mkdir($invitationsDir, 0755, true);
    }
    
    // Generate paths for the invitation file
    $relativeFilePath = 'assets/invitations/' . $invitee['code_invitation'] . '.jpg';
    $absoluteFilePath = __DIR__ . '/../' . $relativeFilePath;
    
    // Format the name according to the updated rules
    $nameParts = array_values(array_filter(explode(' ', $invitee['nom'])));
    $formattedName = '';
    
    $partCount = count($nameParts);
    
    if ($partCount >= 4) {
        // 4 parts or more: check character count first
        $fullName = ucfirst(strtolower($nameParts[0])) . ' ' . 
                   ucfirst(strtolower($nameParts[1])) . ' ' . 
                   ucfirst(strtolower($nameParts[2])) . ' ' . 
                   ucfirst(strtolower($nameParts[3]));
        
        // Count characters including spaces
        if (strlen($fullName) <= 19) {
            $formattedName = $fullName;
        } else {
            // Apply abbreviation: first two complete, third initial, ignore rest
            $formattedName = ucfirst(strtolower($nameParts[0])) . ' ' . 
                            ucfirst(strtolower($nameParts[1])) . ' ' . 
                            ucfirst(substr($nameParts[2], 0, 1)) . '.';
        }
    } elseif ($partCount === 3) {
        // 3 parts: check character count first
        $fullName = ucfirst(strtolower($nameParts[0])) . ' ' . 
                   ucfirst(strtolower($nameParts[1])) . ' ' . 
                   ucfirst(strtolower($nameParts[2]));
        
        // Count characters including spaces
        if (strlen($fullName) <= 19) {
            $formattedName = $fullName;
        } else {
            // Apply abbreviation: first two complete, third initial
            $formattedName = ucfirst(strtolower($nameParts[0])) . ' ' . 
                            ucfirst(strtolower($nameParts[1])) . ' ' . 
                            ucfirst(substr($nameParts[2], 0, 1)) . '.';
        }
    } elseif ($partCount === 2) {
        // 2 parts: both complete (pas de limite de caractères)
        $formattedName = ucfirst(strtolower($nameParts[0])) . ' ' . 
                        ucfirst(strtolower($nameParts[1]));
    } else {
        // 1 part: complete (pas de limite de caractères)
        $formattedName = ucfirst(strtolower($nameParts[0]));
    }
    
    // Create invitation image
    $invitation = generateInvitationImage(
        $formattedName,
        $invitee['code_invitation'],
        $absoluteFilePath
    );
    
    if ($invitation['success']) {
        // Update database with the relative path
        updateInvitationGenerated($invitee['id'], $relativeFilePath);
        
        // Return success response with invitation URL
        jsonResponse([
            'success' => true,
            'message' => 'Invitation generated successfully',
            'data' => [
                'invitation_url' => '/' . $relativeFilePath
            ]
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => $invitation['message']], 500);
    }
} catch (Exception $e) {
    error_log('Invitation Generation Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An error occurred while generating the invitation'], 500);
}

/**
 * Generate invitation image with name and QR code (High Quality)
 */
function generateInvitationImage($fullName, $invitationCode, $outputPath) {
    try {
        // Load the template image
        $template = imagecreatefromjpeg(INVITATION_TEMPLATE);
        
        if (!$template) {
            return [
                'success' => false,
                'message' => 'Failed to load invitation template'
            ];
        }
        
        // Enable high quality rendering
        imagealphablending($template, true);
        imagesavealpha($template, true);
        
        // Get original template dimensions
        $templateWidth = imagesx($template);
        $templateHeight = imagesy($template);
        
        // Create a high-resolution version (2x scale for better quality)
        $scale = 2;
        $highResTemplate = imagecreatetruecolor($templateWidth * $scale, $templateHeight * $scale);
        imagealphablending($highResTemplate, true);
        imagesavealpha($highResTemplate, true);
        
        // Scale up the template with high quality
        imagecopyresampled(
            $highResTemplate, $template,
            0, 0, 0, 0,
            $templateWidth * $scale, $templateHeight * $scale,
            $templateWidth, $templateHeight
        );
        
        // Generate QR code with higher resolution
        $qrSize = QR_CODE_SIZE * $scale;
        $renderer = new ImageRenderer(
            new RendererStyle($qrSize, 4), // Increased border for better quality
            new ImagickImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        
        // Create temporary file for QR code
        $qrCodePath = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
        $writer->writeFile($invitationCode, $qrCodePath);
        
        // Load QR code image
        $qrCode = imagecreatefrompng($qrCodePath);
        
        // Enable anti-aliasing for QR code
        imagealphablending($qrCode, true);
        imagesavealpha($qrCode, true);
        
        // Add QR code to high-res template with smooth scaling
        imagecopyresampled(
            $highResTemplate,
            $qrCode,
            QR_CODE_POS_X * $scale,
            QR_CODE_POS_Y * $scale,
            0,
            0,
            $qrSize,
            $qrSize,
            imagesx($qrCode),
            imagesy($qrCode)
        );
        
        // Text rendering with anti-aliasing and higher quality
        $textColor = imagecolorallocate($highResTemplate, 0, 0, 0);
        
        // Scale font size for high-res version
        $scaledFontSize = NAME_FONT_SIZE * $scale;
        
        // Calculate text dimensions for high-res version
        $bbox = imagettfbbox($scaledFontSize, 0, NAME_FONT, $fullName);
        $textWidth = $bbox[2] - $bbox[0];
        
        // Scale name box boundaries
        $nameBoxLeft = 100 * $scale;
        $nameBoxRight = 630 * $scale;
        $nameBoxWidth = $nameBoxRight - $nameBoxLeft;
        
        // Calculate centered X position
        $centeredX = $nameBoxLeft + ($nameBoxWidth - $textWidth) / 2;
        
        // Add text with anti-aliasing (using imagettftext for better quality)
        imagettftext(
            $highResTemplate,
            $scaledFontSize,
            0,
            $centeredX,
            NAME_POS_Y * $scale,
            $textColor,
            NAME_FONT,
            $fullName
        );
        
        // Scale back down to original size with high quality resampling
        $finalImage = imagecreatetruecolor($templateWidth, $templateHeight);
        imagealphablending($finalImage, true);
        imagesavealpha($finalImage, true);
        
        // High quality downscaling
        imagecopyresampled(
            $finalImage, $highResTemplate,
            0, 0, 0, 0,
            $templateWidth, $templateHeight,
            $templateWidth * $scale, $templateHeight * $scale
        );
        
        // Save with maximum quality (100% JPEG quality)
        imagejpeg($finalImage, $outputPath, 100);
        
        // Clean up
        imagedestroy($template);
        imagedestroy($highResTemplate);
        imagedestroy($finalImage);
        imagedestroy($qrCode);
        unlink($qrCodePath);
        
        return [
            'success' => true
        ];
    } catch (Exception $e) {
        error_log('Image Generation Error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while generating the invitation image'
        ];
    }
}