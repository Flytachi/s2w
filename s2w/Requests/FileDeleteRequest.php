<?php

namespace S2w\Requests;

use Flytachi\Winter\Kernel\Factory\Entity\RequestException;
use Flytachi\Winter\Kernel\Stereotype\RequestObject;

class FileDeleteRequest extends RequestObject
{
    public string $originPath;

    public function __construct(string $originPath)
    {
        if (!preg_match('/^[a-zA-Z0-9\/.\-=]+$/', $originPath)) {
            if (preg_match('/[^a-zA-Z0-9\/.\-=]/', $originPath, $matches)) {
                throw new RequestException(
                    "Invalid character '{$matches[0]}' in the 'originPath' field"
                );
            } else {
                throw new RequestException(
                    "The 'originPath' field contains forbidden characters"
                );
            }
        }
        $this->originPath = trim($originPath, '/');
    }
}