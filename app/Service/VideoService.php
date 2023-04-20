<?php

namespace App\Service;

use Hyperf\Di\Annotation\Inject;

class VideoService
{

    #[Inject]
    protected S3Service $s3Service;


}
