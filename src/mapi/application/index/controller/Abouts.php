<?php
namespace app\index\controller;
use think\Controller;

class Abouts extends Controller
{
    public function index()
    {
        return $this->fetch('/aboutus');
    }
}
