<?php

namespace App\Trading;

class Currency{

    public $code;

    public function __construct($code){
        $this->code = $code;
    }

}