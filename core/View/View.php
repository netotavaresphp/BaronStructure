<?php

declare(strict_types=1);

namespace Core\View;

use RuntimeException;

final class View
{
  public function __construct(private string $viewsPath) {}

  public function render(string $view, array $data = [], string $layout = 'layouts/main'): string
  {
    $viewFile = rtrim($this->viewsPath, '/') . '/' . $view . '.php';
    $layoutFile = rtrim($this->viewsPath, '/') . '/' . $layout . '.php';

    if (!is_file($viewFile)) {
      throw new RuntimeException("View not found: {$viewFile}");
    }
    if (!is_file($layoutFile)) {
      throw new RuntimeException("Layout not found: {$layoutFile}");
    }

    extract($data, EXTR_SKIP);

    ob_start();
    require $viewFile;
    $content = ob_get_clean();

    ob_start();
    require $layoutFile;
    return (string)ob_get_clean();
  }
}