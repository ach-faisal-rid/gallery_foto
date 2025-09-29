<?php
namespace controllers;

require_once __DIR__ . '/../../model/gallery.php';

use Model\Gallery;

class GalleryController
{
    private $maxFileSize = 5242880; // 5 MB
    private $allowedMimes = ['image/jpeg','image/png','image/gif','image/webp'];

    private function getUploadsDir()
    {
        $uploads = realpath(__DIR__ . '/../../uploads');
        if ($uploads === false) {
            $uploads = __DIR__ . '/../../uploads';
            if (!is_dir($uploads)) mkdir($uploads, 0755, true);
            $uploads = realpath($uploads);
        }
        return $uploads;
    }

    private function makeFileUrl($filePath)
    {
        if (empty($filePath)) return null;
        if (filter_var($filePath, FILTER_VALIDATE_URL)) return $filePath;
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), "\\/");
        return $scheme . $host . $scriptDir . '/' . ltrim($filePath, '\\/');
    }

    private function downloadExternalImage($url)
    {
        // 1) Try HEAD request to get headers
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'GalleryApp/1.0'
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);

        // If it's an image and size is acceptable, download it
        if ($contentType && stripos($contentType, 'image/') === 0) {
            if ($contentLength > 0 && $contentLength > $this->maxFileSize) {
                return [false, 'File terlalu besar'];
            }
            return $this->fetchAndSave($url, $contentType);
        }

        // If content is HTML (likely a Pinterest pin page), try to fetch page and extract og:image
        // Fetch page
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'GalleryApp/1.0'
        ]);
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!$html) return [false, 'Gagal mengambil halaman eksternal'];

        // Extract og:image
        if (preg_match('/<meta\s+property="og:image"\s+content="([^"]+)"/i', $html, $m)) {
            $imgUrl = html_entity_decode($m[1]);
            // download that image
            return $this->fetchAndSave($imgUrl);
        }

        // fallback: look for <link rel="image_src"
        if (preg_match('/<link[^>]+rel=["\']image_src["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $m2)) {
            $imgUrl = html_entity_decode($m2[1]);
            return $this->fetchAndSave($imgUrl);
        }

        return [false, 'Tidak menemukan gambar pada URL tersebut'];
    }

    private function fetchAndSave($imgUrl, $expectedMime = null)
    {
        // HEAD check for image
        $ch = curl_init($imgUrl);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'GalleryApp/1.0'
        ]);
        curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);

        if ($contentType && stripos($contentType, 'image/') !== 0) {
            return [false, 'URL tidak mengembalikan image'];
        }
        if ($contentLength > 0 && $contentLength > $this->maxFileSize) {
            return [false, 'File terlalu besar'];
        }

        // Download image body
        $ch = curl_init($imgUrl);
        $fp = tmpfile();
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_USERAGENT => 'GalleryApp/1.0'
        ]);
        $ok = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if (!$ok) return [false, 'Gagal mendownload gambar: ' . $err];

        // determine actual mime via finfo
        $meta = stream_get_meta_data($fp);
        fseek($fp, 0);
        $tmpfilePath = stream_get_meta_data($fp)['uri'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpfilePath);
        finfo_close($finfo);

        if (!in_array($mime, $this->allowedMimes)) {
            fclose($fp);
            return [false, 'Tipe file tidak didukung: ' . $mime];
        }

        // check size
        $size = filesize($tmpfilePath);
        if ($size > $this->maxFileSize) { fclose($fp); return [false, 'File terlalu besar']; }

        // save to uploads dir
        $ext = '';
        switch ($mime) {
            case 'image/jpeg': $ext = '.jpg'; break;
            case 'image/png': $ext = '.png'; break;
            case 'image/gif': $ext = '.gif'; break;
            case 'image/webp': $ext = '.webp'; break;
            default: $ext = '';
        }
        $uploads = $this->getUploadsDir();
        $filename = uniqid('img_') . $ext;
        $dest = $uploads . DIRECTORY_SEPARATOR . $filename;
        // move temp file
        if (!rename($tmpfilePath, $dest)) {
            // fallback copy
            $destFp = fopen($dest, 'w');
            fseek($fp, 0);
            stream_copy_to_stream($fp, $destFp);
            fclose($destFp);
        }
        fclose($fp);

        // return relative path
        $rel = 'uploads/' . $filename;
        return [true, $rel];
    }
    // GET /api/galleries
    public function index()
    {
        $model = new Gallery();
        $items = $model->getAll();
        // attach public URL for each item
        foreach ($items as &$it) {
            $it['file_url'] = $this->makeFileUrl($it['file'] ?? null);
        }
        http_response_code(200);
        echo json_encode(['message' => 'Daftar galeri', 'data' => $items]);
        exit();
    }

    // GET /api/galleries/{id}
    public function show($id)
    {
        $model = new Gallery();
        $item = $model->findById($id);
        if (!$item) { http_response_code(404); echo json_encode(['message' => 'Galeri tidak ditemukan']); exit(); }
        $item['file_url'] = $this->makeFileUrl($item['file'] ?? null);
        http_response_code(200);
        echo json_encode(['message' => 'Detail galeri', 'data' => $item]);
        exit();
    }

    // POST /api/galleries
    public function create()
    {
        $request = json_decode(file_get_contents('php://input'), true);
        if (!$request || empty($request['title'])) { http_response_code(400); echo json_encode(['message' => 'Title is required']); exit(); }

        // Handle 'file' external URL: download and replace with local uploads path
        if (!empty($request['file']) && filter_var($request['file'], FILTER_VALIDATE_URL)) {
            list($ok, $result) = $this->downloadExternalImage($request['file']);
            if (!$ok) { http_response_code(400); echo json_encode(['message' => 'Gagal mengolah file eksternal: ' . $result]); exit(); }
            // result is relative path like uploads/xxx
            $request['file'] = $result;
        }

        $model = new Gallery();
        $res = $model->create($request);
    if ($res && is_array($res)) { $res['file_url'] = $this->makeFileUrl($res['file'] ?? null); http_response_code(201); echo json_encode(['message' => 'Galeri dibuat', 'data' => $res]); exit(); }

        http_response_code(500); echo json_encode(['message' => 'Gagal membuat galeri']); exit();
    }

    // PUT /api/galleries/{id}
    public function update($id)
    {
        $request = json_decode(file_get_contents('php://input'), true);
        if (!$request) { http_response_code(400); echo json_encode(['message' => 'Invalid JSON']); exit(); }

        $model = new Gallery();
        $existing = $model->findById($id);
        if (!$existing) { http_response_code(404); echo json_encode(['message' => 'Galeri tidak ditemukan']); exit(); }

        // If file is external URL, download and replace
        if (!empty($request['file']) && filter_var($request['file'], FILTER_VALIDATE_URL)) {
            list($ok, $result) = $this->downloadExternalImage($request['file']);
            if (!$ok) { http_response_code(400); echo json_encode(['message' => 'Gagal mengolah file eksternal: ' . $result]); exit(); }
            $request['file'] = $result;
        }

        $res = $model->update($id, $request);
    if ($res) { $item = $model->findById($id); $item['file_url'] = $this->makeFileUrl($item['file'] ?? null); http_response_code(200); echo json_encode(['message' => 'Galeri diperbarui', 'data' => $item]); exit(); }

        http_response_code(500); echo json_encode(['message' => 'Gagal memperbarui galeri']); exit();
    }

    // DELETE /api/galleries/{id}
    public function delete($id)
    {
        $model = new Gallery();
        $existing = $model->findById($id);
        if (!$existing) { http_response_code(404); echo json_encode(['message' => 'Galeri tidak ditemukan']); exit(); }

        $res = $model->delete($id);
        if ($res) { http_response_code(200); echo json_encode(['message' => 'Galeri dihapus']); exit(); }

        http_response_code(500); echo json_encode(['message' => 'Gagal menghapus galeri']); exit();
    }

    // POST /api/galleries/{id}/image  -- upload image for an existing gallery item
    public function uploadImage($id)
    {
        // Check entry exists
        $model = new Gallery();
        $existing = $model->findById($id);
        if (!$existing) { http_response_code(404); echo json_encode(['message' => 'Galeri tidak ditemukan']); exit(); }

        if (!isset($_FILES['image'])) {
            http_response_code(400);
            echo json_encode(['message' => 'File image tidak ditemukan pada field "image"']);
            exit();
        }

        $file = $_FILES['image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['message' => 'Upload error code: ' . $file['error']]);
            exit();
        }

        if ($file['size'] > $this->maxFileSize) {
            http_response_code(400);
            echo json_encode(['message' => 'File terlalu besar']);
            exit();
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $this->allowedMimes)) {
            http_response_code(400);
            echo json_encode(['message' => 'Tipe file tidak didukung: ' . $mime]);
            exit();
        }

        $ext = '';
        switch ($mime) {
            case 'image/jpeg': $ext = '.jpg'; break;
            case 'image/png': $ext = '.png'; break;
            case 'image/gif': $ext = '.gif'; break;
            case 'image/webp': $ext = '.webp'; break;
        }

        $uploads = $this->getUploadsDir();
        $filename = uniqid('img_') . $ext;
        $dest = $uploads . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            http_response_code(500);
            echo json_encode(['message' => 'Gagal menyimpan file']);
            exit();
        }

        $rel = 'uploads/' . $filename;
        // update DB
        $ok = $model->update($id, ['file' => $rel]);
        if ($ok) {
            $item = $model->findById($id);
            $item['file_url'] = $this->makeFileUrl($item['file'] ?? null);
            http_response_code(200);
            echo json_encode(['message' => 'File terupload', 'data' => $item]);
            exit();
        }

        http_response_code(500);
        echo json_encode(['message' => 'Gagal mengupdate record dengan file']);
        exit();
    }
}
