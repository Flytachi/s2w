<?php

namespace S2w\Controllers;

use S2w\Requests\FileCreateRequest;
use S2w\Requests\FileDeleteRequest;
use S2w\Requests\FolderRequest;
use S2w\Requests\ShowRequest;
use S2w\S2wMiddleware;
use S2w\Services\S2wService;
use Flytachi\Winter\Kernel\Stereotype\Output\ResponseJson;
use Flytachi\Winter\Kernel\Stereotype\RestController;
use Flytachi\Winter\Mapping\Annotation\PostMapping;
use Flytachi\Winter\Mapping\Annotation\RequestMapping;

#[S2wMiddleware]
#[RequestMapping('ctl')]
class ControlController extends RestController
{
    #[PostMapping]
    public function show(): ResponseJson
    {
        $request = ShowRequest::json();
        return new ResponseJson(
            (new S2wService)->show($request)
        );
    }

    #[PostMapping('folder')]
    public function createFolder(): void
    {
        $request = FolderRequest::json();
        (new S2wService)->createFolder($request);
    }

    #[PostMapping('folder/delete')]
    public function deleteFolder(): void
    {
        $request = FolderRequest::json();
        (new S2wService)->deleteFolder($request);
    }

    #[PostMapping('file')]
    public function createFile(): ResponseJson
    {
        $request = FileCreateRequest::formData();
        return new ResponseJson(
            (new S2wService)->createFile($request)
        );
    }

    #[PostMapping('file/delete')]
    public function delete(): void
    {
        $request = FileDeleteRequest::json();
        (new S2wService)->deleteFile($request);
    }
}
