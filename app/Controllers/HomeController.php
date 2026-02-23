<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Core\View\View;

final class HomeController
{
    public function __construct(private View $view) {}

    public function index(Request $request): Response
    {
        $html = $this->view->render('home/index', [
            'title' => 'MVC OK',
            'time' => date('Y-m-d H:i:s'),
        ]);

        return Response::html($html);
    }
}
