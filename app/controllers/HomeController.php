<?php

namespace app\controllers;

use Controller;
use Session;

class HomeController extends Controller
{
    public function actionIndex(): void
    {
        // Etapa 24: el usuario con sesión va a su inicio; el visitante ve el home público.
        if (Session::estaAutenticado()) {
            $this->redirigir(Session::rutaInicio());
        }

        $this->render('home/index', ['titulo' => 'Inicio']);
    }
}
