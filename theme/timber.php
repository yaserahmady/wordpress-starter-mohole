<?php

/**
 * If you are installing Timber as a Composer dependency in your theme, you'll need this block
 * to load your dependencies and initialize Timber. If you are using Timber via the WordPress.org
 * plug-in, you can safely delete this block.
 */
$composer_autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
    $timber = new Timber\Timber();
}

/**
 * This ensures that Timber is loaded and available as a PHP class.
 * If not, it gives an error message to help direct developers on where to activate
 */
if (!class_exists('Timber')) {

    add_action(
        'admin_notices',
        function () {
            echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url(admin_url('plugins.php#timber')) . '">' . esc_url(admin_url('plugins.php')) . '</a></p></div>';
        }
    );

    add_filter(
        'template_include',
        function ($template) {
            return get_stylesheet_directory() . '/static/no-timber.html';
        }
    );
    return;
}

/**
 * Sets the directories (inside your theme) to find .twig files
 */
Timber::$dirname = array('../templates', '../views');

/**
 * By default, Timber does NOT autoescape values. Want to enable Twig's autoescape?
 * No prob! Just set this value to true
 */
Timber::$autoescape = false;

/**
 * We're going to configure our theme inside of a subclass of Timber\Site
 * You can move this to its own file and include here via php's include("MySite.php")
 */
class CustomTheme extends Timber\Site
{
    /** Add timber support. */
    public function __construct()
    {
        add_action('after_setup_theme', array($this, 'theme_supports'));
        add_filter('timber/context', array($this, 'add_to_context'));
        add_filter('timber/twig', array($this, 'add_to_twig'));
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        parent::__construct();

        // Gets the parent folder for {{ theme.link }} so we can keep the current folder structure.
        // Fixes https://github.com/timber/starter-theme/issues/105
        $theme = new class() extends Timber\Theme
        {
            public function link()
            {
                return dirname(get_stylesheet_directory_uri());
            }
        };
        $this->theme = $theme;
    }
    /** This is where you can register custom post types. */
    public function register_post_types()
    {
    }
    /** This is where you can register custom taxonomies. */
    public function register_taxonomies()
    {
    }

    /** This is where you add some context
     *
     * @param string $context context['this'] Being the Twig's {{ this }}.
     */
    public function add_to_context($context)
    {
        // Advanced Custom Fields
        if (class_exists('ACF')) {
            $context['option'] = get_fields('option');
            $context['options'] = get_fields('options');
        }

        // Polylang
        if (function_exists('pll_the_languages')) {
            $context['languages'] = pll_the_languages(array('raw' => 1));
        }
        if (function_exists('pll_current_language')) {
            $context['current_language'] = pll_current_language();
        }
        if (function_exists('pll_home_url')) {
            $context['pll_home_url'] = pll_home_url();
        }

        // Tools
        $context['current_url'] = Timber\URLHelper::get_current_url();
        $context['menu'] = new Timber\Menu();
        $context['site'] = $this;
        $context['theme'] = $this->theme;
        return $context;
    }

    public function theme_supports()
    {
        // Add default posts and comments RSS feed links to head.
        add_theme_support('automatic-feed-links');

        /*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
        add_theme_support('title-tag');

        /*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
        add_theme_support('post-thumbnails');

        /*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
        add_theme_support(
            'html5',
            array(
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
            )
        );

        /*
		 * Enable support for Post Formats.
		 *
		 * See: https://codex.wordpress.org/Post_Formats
		 */
        add_theme_support(
            'post-formats',
            array(
                'aside',
                'image',
                'video',
                'quote',
                'link',
                'gallery',
                'audio',
            )
        );

        add_theme_support('menus');
    }

    /** This is where you can add your own functions to twig.
     *
     * @param string $twig get extension.
     */
    public function add_to_twig($twig)
    {
        $twig->addExtension(new Twig\Extension\StringLoaderExtension());

        // Polylang

        if (function_exists('pll_permalink')) {
            $twig->addFunction(new Timber\Twig_Function('pll_permalink', function ($id) {
                return get_permalink(pll_get_post($id, pll_current_language()));
            }));
        }
        if (function_exists('pll_e')) {
            $twig->addFunction(new Timber\Twig_Function('pll_e', 'pll_e'));
        }
        if (function_exists('pll_')) {
            $twig->addFunction(new Timber\Twig_Function('pll_', 'pll_'));
        }

        // Tools
        $twig->addFilter(new Timber\Twig_Filter('is_current_url', function ($link) {
            return (Timber\URLHelper::get_current_url() == $link) ? true : false;
        }));

        return $twig;
    }
}

new CustomTheme();
