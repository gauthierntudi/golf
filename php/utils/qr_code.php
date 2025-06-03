<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Génère un QR code pour une URL donnée
 * 
 * @param string $url L'URL à encoder dans le QR code
 * @return string Le chemin du fichier QR code généré
 */
function generateQrCode($url) {
    // Créer le répertoire pour les QR codes s'il n'existe pas
    $qrDir = __DIR__ . '/../../assets/qrcodes';
    if (!file_exists($qrDir)) {
        mkdir($qrDir, 0755, true);
    }
    
    // Générer un nom de fichier unique
    $filename = $qrDir . '/' . md5($url . time()) . '.png';
    
    // Créer le QR code
    $renderer = new ImageRenderer(
        new RendererStyle(400),
        new ImagickImageBackEnd()
    );
    
    $writer = new Writer($renderer);
    $writer->writeFile($url, $filename);
    
    return $filename;
}