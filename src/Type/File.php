<?php

namespace Norm\Type;

use Norm\Exception\NormException;

class File {
    protected $baseDirectory;

    protected $type;

    protected $path;

    protected $actualPath;

    protected $name;

    protected $size;

    public function __construct($baseDirectory, $path)
    {
        $this->baseDirectory = rtrim($baseDirectory, '/');
        $this->path = trim($path, '/');
        $this->actualPath = $this->baseDirectory . '/' . $this->path;
    }

    public function isExists()
    {
        return file_exists($this->actualPath);
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getBaseDirectory()
    {
        return $this->baseDirectory;
    }

    public function getName()
    {
        if (null === $this->name) {
            $this->name = basename($this->actualPath);
        }
        return $this->name;
    }

    public function getSize()
    {
        if (null === $this->size) {
            $this->size = filesize($this->actualPath);
        }
        return $this->size;
    }
}