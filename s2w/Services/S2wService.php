<?php

namespace S2w\Services;

use S2w\Requests\FileCreateRequest;
use S2w\Requests\FileDeleteRequest;
use S2w\Requests\FolderRequest;
use S2w\Requests\ShowRequest;
use Flytachi\Winter\Base\HttpCode;
use Flytachi\Winter\Kernel\Exception\ClientError;
use Flytachi\Winter\Kernel\Kernel;
use Flytachi\Winter\Kernel\Stereotype\Service;

class S2wService extends Service
{
    private string $store = 's2w/chest';

    private function getUrlPoint(string $endPoint): string
    {
        $urlPoint = env('S2W_URL_POINT');
        return ($urlPoint ?: SERVER_SCHEME)
            . '/' . $endPoint;
    }

    private function getPath(): string
    {
        $path = Kernel::$pathStorage . '/' . $this->store;
        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true)) {
                ClientError::throw("S2w: Main folder \"{$this->store}\" don't created", HttpCode::INSUFFICIENT_STORAGE);
            }
        }
        return $path;
    }

    public function getImageById(string $imageId): string
    {
        $origin = base64_decode($imageId);
        $path = Kernel::$pathStorage . '/' . $origin;
        if (!file_exists($path)) {
            ClientError::throw("File is not exist", HttpCode::NOT_FOUND);
        }
        return $path;
    }

    public function find(string $imageId): ?array
    {
        $origin = base64_decode($imageId);
        $path = Kernel::$pathStorage . '/' . $origin;
        if (!is_file($path)) return null;
        return [
            'id' => $imageId,
            'name' => basename($origin),
            'url' => $this->getUrlPoint($imageId),
            'originPath' => str_replace($this->store, '', $origin),
        ];
    }

    public function findList(array $imageIds): array
    {
        $result = [];
        foreach ($imageIds as $imageId) {
            $info = $this->find($imageId);
            if ($info) $result[] = $info;
        }
        return $result;
    }

    public function show(ShowRequest $request): array
    {
        $path = $this->getPath() . '/' . $request->prefix;
        if (!is_dir($path)) {
            ClientError::throw("S2w: Folder \"$request->prefix\" is not exist", HttpCode::BAD_REQUEST);
        }
        $prefix = str_replace('//', '/', '/' . $request->prefix . '/');
        $scanInfo = scandir($path, SCANDIR_SORT_DESCENDING);
        $scanInfo = array_diff($scanInfo, ['.', '..', '.gitignore']);

        usort($scanInfo, function($a, $b) use ($path) {
            $aIsDir = is_dir($path . '/' . $a);
            $bIsDir = is_dir($path . '/' . $b);
            if ($aIsDir && !$bIsDir) {
                return -1;
            } elseif (!$aIsDir && $bIsDir) {
                return 1;
            }

            return strcmp($a, $b);
        });

        $result = [];
        foreach ($scanInfo as $item) {
            if (is_dir($path . '/' . $item)) {
                $object = [
                    'type' => 'FOLDER',
                    'name' => $item,
                    'originPath' => $prefix . $item,
                ];
            } elseif (is_file($path . '/' . $item)) {
                $id = base64_encode($this->store . $prefix . $item);
                $object = [
                    'type' => 'FILE',
                    'id' => $id,
                    'name' => $item,
                    'url' => $this->getUrlPoint($id),
                    'originPath' => $prefix . $item,
                ];
            } else {
                continue;
            }
            $result[] = $object;
        }

        return $result;
    }

    public function createFile(FileCreateRequest $request): array
    {
        if (empty($request->file['name']))
            ClientError::throw('S2w: Error file not name', HttpCode::INSUFFICIENT_STORAGE);
        // Upload File
        if ($request->file['error'] !== UPLOAD_ERR_OK)
            ClientError::throw('S2w: Error loading to temporary folder', HttpCode::INSUFFICIENT_STORAGE);

        $fileTmpPath = $request->file['tmp_name'];
        $fileNameCms = explode(".", $request->file['name']);
        $fileExtension = strtolower(end($fileNameCms));
        $newFileName = ($request->name ?? sha1(time() . $request->file['name']));

        $path = $this->getPath();
        $mainFolder = $path . '/' . ($request->prefix ? $request->prefix . '/' : '');
        if (!is_dir($mainFolder)) {
            ClientError::throw("S2w: Folder not found (/{$request->prefix})", HttpCode::BAD_REQUEST);
        }

        $originPath = $this->compressToWebP(
            $fileTmpPath,
            $mainFolder . $newFileName,
            $request->file['type'],
            $fileExtension,
            50
        );
        $mediaOriginPos = strpos($originPath, $this->store);
        $id = base64_encode(substr($originPath, $mediaOriginPos));

        return [
            'id' => $id,
            'url' => $this->getUrlPoint($id),
            'originPath' => '/' . ($mediaOriginPos !== false)
                ? substr($originPath, $mediaOriginPos +1+ strlen($this->store))
                : ''
        ];
    }

    public function deleteFile(FileDeleteRequest $request): void
    {
        $path = $this->getPath();
        if (!file_exists($path . '/' . $request->originPath)) {
            ClientError::throw("S2w: File \"$request->originPath\" is not exist", HttpCode::BAD_REQUEST);
        }

        if (!unlink($path . '/' . $request->originPath)) {
            ClientError::throw("S2w: File \"$request->originPath\" don't deleted", HttpCode::INSUFFICIENT_STORAGE);
        }
    }

    public function createFolder(FolderRequest $request): void
    {
        $path = $this->getPath();
        if (!is_dir($path . '/' . $request->prefix)) {
            ClientError::throw("S2w: Folder not found \"$request->prefix\"", HttpCode::BAD_REQUEST);
        }

        $path = $path . '/' . $request->prefix;
        if (is_dir($path . '/' . $request->name)) {
            ClientError::throw("S2w: Folder \"$request->name\" is already exist", HttpCode::BAD_REQUEST);
        }

        if (!mkdir($path . '/' . $request->name, 0777, true)) {
            ClientError::throw("S2w: Folder \"$request->name\" don't created", HttpCode::INSUFFICIENT_STORAGE);
        }
    }

    public function deleteFolder(FolderRequest $request): void
    {
        $path = $this->getPath();
        if (!is_dir($path . '/' . $request->prefix)) {
            ClientError::throw("S2w: Folder not found \"$request->prefix\"", HttpCode::BAD_REQUEST);
        }

        $path = $path . '/' . $request->prefix;
        if (!is_dir($path . '/' . $request->name)) {
            ClientError::throw("S2w: Folder \"$request->name\" is not exist", HttpCode::BAD_REQUEST);
        }

        $files = scandir($path . '/' . $request->name);
        if (count($files) > 2) {
            ClientError::throw("S2w: Folder \"$request->name\" is not empty", HttpCode::BAD_REQUEST);
        }

        if (!rmdir($path . '/' . $request->name)) {
            ClientError::throw("S2w: Folder \"$request->name\" don't deleted", HttpCode::INSUFFICIENT_STORAGE);
        }
    }

    private function compressToWebP(
        string $source,
        string $destination,
        string $mime,
        string $extension,
        int $quality = 70
    ): string {
        switch ($mime) {
            case 'image/jpeg':
                $path = $destination . '.webp';
                if (file_exists($path)) {
                    ClientError::throw("File is exist", HttpCode::BAD_REQUEST);
                }
                $image = imagecreatefromjpeg($source);
                imagewebp($image, $path, $quality);
                break;
            case 'image/png':
                $path = $destination . '.webp';
                if (file_exists($path)) {
                    ClientError::throw("File is exist", HttpCode::BAD_REQUEST);
                }
                $image = imagecreatefrompng($source);
                imagewebp($image, $path, $quality);
                break;
            default:
                $path = $destination . '.' . $extension;
                if (file_exists($path)) {
                    ClientError::throw("File is exist", HttpCode::BAD_REQUEST);
                }
                move_uploaded_file($source, $path);
                break;
        }
        return $path;
    }
}
