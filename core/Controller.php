<?php
abstract class Controller {
    private static string $viewsPath = '';

    protected function render(string $view, array $data = []): void {
        $viewsPath = __DIR__ . '/../app/Views/';
        extract($data);

        // navItem() uses `global $activePage` — publish it so it resolves correctly
        $GLOBALS['activePage'] = isset($activePage) ? $activePage : 'Dashboard';

        require $viewsPath . 'layouts/main.php';
        require $viewsPath . $view . '.php';
        require $viewsPath . 'layouts/footer.php';
    }
}
