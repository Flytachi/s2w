<?php

namespace S2w\Controllers;

use S2w\S2wMiddleware;
use S2w\Services\S2wService;
use Flytachi\Winter\Kernel\Factory\Entity\Request;
use Flytachi\Winter\Kernel\Stereotype\Output\ResponseContent;
use Flytachi\Winter\Kernel\Stereotype\Output\ResponseJson;
use Flytachi\Winter\Kernel\Stereotype\RestController;
use Flytachi\Winter\Mapping\Annotation\GetMapping;
use Flytachi\Winter\Mapping\Annotation\PostMapping;
use Flytachi\Winter\Mapping\Annotation\RequestMapping;

#[S2wMiddleware]
#[RequestMapping]
class StoreController extends RestController
{
    #[GetMapping('{imageId}')]
    public function get(string $imageId): ResponseContent
    {
        $filePath = (new S2wService)->getImageById($imageId);
        return ResponseContent::file($filePath);
    }

    #[GetMapping('find/{imageId}')]
    public function find(string $imageId): ResponseJson
    {
        return new ResponseJson(
            (new S2wService)->find($imageId)
        );
    }

    #[PostMapping('find')]
    public function findImages(): ResponseJson
    {
        $request = Request::json()
            ->valid('imageIds', 'is_array');

        return new ResponseJson(
            (new S2wService)->findList($request->imageIds)
        );
    }
}
