<?php

namespace S2w\Requests;

use Flytachi\Winter\Kernel\Factory\Entity\RequestException;
use Flytachi\Winter\Kernel\Stereotype\RequestObject;

class FileCreateRequest extends RequestObject
{
    public string $prefix;
    public array $file;
    public ?string $name = null;

    public function __construct(string $prefix = '/', ?string $name = null)
    {
        if (!empty($prefix)) {
            if (!preg_match('/^[a-zA-Z0-9\/]+$/', $prefix)) {
                if (preg_match('/[^a-zA-Z0-9\/]/', $prefix, $matches)) {
                    throw new RequestException("Invalid character '{$matches[0]}' in the 'prefix' field");
                } else {
                    throw new RequestException("The 'prefix' field contains forbidden characters");
                }
            }
        }
        $this->prefix = trim($prefix, '/');

        if (!isset($_FILES['file']) || !is_array($_FILES['file']))
            throw new RequestException('Required field \'file\' not found');
        $this->file = $_FILES['file'];

        if (!empty($name)) {
            if (!preg_match('/^[a-zA-Z0-9\/.\-=]+$/', $name)) {
                if (preg_match('/[^a-zA-Z0-9\/.\-=]/', $name, $matches)) {
                    throw new RequestException(
                        "Invalid character '{$matches[0]}' in the 'name' field"
                    );
                } else {
                    throw new RequestException(
                        "The 'name' field contains forbidden characters"
                    );
                }
            }
            $this->name = $name;
        }
    }
}