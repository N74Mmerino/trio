<?php

namespace app\controllers;

use Controller;
use Session;

class HomeController extends Controller
{
    public function actionIndex(): void
    {
        $this->redirigir(Session::estaAutenticado() ? Session::rutaInicio() : 'auth/login');
    }
}
