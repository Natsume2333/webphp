<?php
namespace app\index\controller;
use think\Controller;

class Joinus extends Controller
{
    public function index()
    {
        return $this->fetch('/joinus');
    }
}

