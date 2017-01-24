<?php

namespace Kore;

class TemplateHandler
{
    private $twig;

    public function __construct()
    {
        $this->twig = new \Twig_Environment(
            new \Twig_Loader_Filesystem(__DIR__ . '/../')
        );
    }

    public function render(string $template, $data = []): string
    {
        $template = $this->twig->load($template);
        return $template->render($data);
    }
}
