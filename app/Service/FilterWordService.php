<?php

namespace App\Service;



use DfaFilter\SensitiveHelper;

class FilterWordService
{
    public function filter($content)
    {
        return true;
        $wordFilePath = 'tests/data/words.txt';
        $handle = SensitiveHelper::init()->setTreeByFile($wordFilePath);
        return $handle->islegal($content);
    }
}
