<?php
namespace app\index\controller;
use think\Controller;

class Linkus extends Controller
{
    public function index()
    {
        return $this->fetch('/linkus');
    }
}

