<?php

namespace Geoks\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

class Image
{
    /**
     * @var File
     */
    protected $image;

    public function __toString()
    {
        return (string) $this->image->getFilename();
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param mixed $image
     */
    public function setImage($image)
    {
        if ($image instanceof File) {
            $this->image = $image;
        }
    }
}
