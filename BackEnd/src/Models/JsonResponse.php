<?php namespace App\Models;

class JsonResponse{
    public $data;
    public $message;

    public function __construct(){
        $this -> data = [];
    }
}