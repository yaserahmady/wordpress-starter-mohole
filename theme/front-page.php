<?php

$context = Timber::context();

$timber_post = new Timber\Post();
$context['post'] = $timber_post;

if (is_front_page()) {
    Timber::render(['homepage.twig', 'front-page.twig', 'home.twig'], $context);
}
