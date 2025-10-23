<?php
namespace App\Controllers;

class BaseController {
    public function getUserId($request) {
        return $request->getAttribute('user_id');
    }
}