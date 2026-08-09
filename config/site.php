<?php

return [
    'platform_domain' => env('PLATFORM_DOMAIN', 'pdi_saas.test'),

    'domain_verification' => [
        'txt_prefix' => env('DOMAIN_TXT_PREFIX', 'pdi-verify'),
        'record_name' => '_pdi-verify',
        'token_bytes' => 32,
        'doh_url' => env('DNS_DOH_URL', 'https://cloudflare-dns.com/dns-query'),
        'doh_enabled' => (bool) env('DNS_DOH_ENABLED', true),
        'native_enabled' => (bool) env('DNS_NATIVE_ENABLED', true),
    ],

    'default_theme' => [
        'primary' => '#4f46e5',
        'secondary' => '#0ea5e9',
        'accent' => '#f59e0b',
        'background' => '#ffffff',
        'text' => '#0f172a',
        'muted' => '#64748b',
        'font' => 'Inter',
        'radius' => 'medium',
        'button_style' => 'filled',
        'animation' => 'fade',
        'header_style' => 'sticky',
        'footer_style' => 'dark',
        'chat_enabled' => true,
        'chat_title' => 'Asistente virtual',
        'chat_welcome' => 'Hola, ¿en qué puedo ayudarte?',
    ],

    'fonts' => ['Inter', 'Poppins', 'Roboto', 'Montserrat', 'Space Grotesk', 'Playfair Display'],

    'radius_options' => ['none', 'small', 'medium', 'large', 'full'],

    'button_styles' => ['filled', 'outline', 'ghost'],

    'animations' => ['none', 'fade', 'slide', 'zoom'],

    'blocks' => [
        'navbar' => [
            'label' => 'Barra de navegación',
            'icon' => 'M4 6h16M4 12h16M4 18h16',
            'variants' => [
                'default' => [
                    'label' => 'Predeterminado',
                    'content' => ['logo' => '', 'links' => [['label' => 'Inicio', 'url' => '#home'], ['label' => 'Servicios', 'url' => '#servicios'], ['label' => 'Contacto', 'url' => '#contacto']], 'cta' => ['label' => 'Contáctanos', 'url' => '#contacto']],
                ],
            ],
        ],
        'hero' => [
            'label' => 'Hero',
            'icon' => 'M4 6h16M4 10h16M4 14h10',
            'variants' => [
                'centered' => ['label' => 'Centrado', 'content' => ['badge' => '', 'title' => 'Tu empresa, con presencia digital inteligente', 'subtitle' => 'Descripción breve de lo que hace tu empresa.', 'primary_cta' => ['label' => 'Solicitar cotización', 'url' => '#contacto'], 'secondary_cta' => ['label' => 'Conócenos', 'url' => '#nosotros']]],
                'split' => ['label' => 'Dividido', 'content' => ['badge' => '', 'title' => 'Tu empresa, con presencia digital inteligente', 'subtitle' => 'Descripción breve de lo que hace tu empresa.', 'image' => '', 'primary_cta' => ['label' => 'Solicitar cotización', 'url' => '#contacto'], 'secondary_cta' => ['label' => 'Conócenos', 'url' => '#nosotros']]],
                'fullscreen' => ['label' => 'Pantalla completa', 'content' => ['badge' => '', 'title' => 'Tu empresa, con presencia digital inteligente', 'subtitle' => 'Descripción breve de lo que hace tu empresa.', 'image' => '', 'primary_cta' => ['label' => 'Solicitar cotización', 'url' => '#contacto']]],
                'minimal' => ['label' => 'Minimal', 'content' => ['badge' => '', 'title' => 'Tu empresa, con presencia digital inteligente', 'subtitle' => 'Descripción breve de lo que hace tu empresa.']],
            ],
        ],
        'services' => [
            'label' => 'Servicios',
            'icon' => 'M12 6v6m0 0l4-4m-4 4l-4-4',
            'variants' => [
                'cards' => ['label' => 'Tarjetas', 'content' => ['title' => 'Nuestros servicios', 'items' => [['icon' => 'sparkles', 'title' => 'Servicio 1', 'description' => 'Describe el primer servicio.'], ['icon' => 'star', 'title' => 'Servicio 2', 'description' => 'Describe el segundo servicio.'], ['icon' => 'shield', 'title' => 'Servicio 3', 'description' => 'Describe el tercer servicio.']]]],
                'grid' => ['label' => 'Rejilla', 'content' => ['title' => 'Nuestros servicios', 'items' => [['title' => 'Servicio 1', 'description' => 'Descripción.'], ['title' => 'Servicio 2', 'description' => 'Descripción.'], ['title' => 'Servicio 3', 'description' => 'Descripción.']]]],
            ],
        ],
        'products' => [
            'label' => 'Productos',
            'icon' => 'M12 8c-3 0-5 2-5 4s2 4 5 4 5-2 5-4-2-4-5-4z',
            'variants' => [
                'cards' => ['label' => 'Tarjetas', 'content' => ['title' => 'Nuestros productos', 'items' => [['image' => '', 'title' => 'Producto 1', 'description' => 'Descripción del producto 1.', 'price' => ''], ['image' => '', 'title' => 'Producto 2', 'description' => 'Descripción del producto 2.', 'price' => '']]]],
            ],
        ],
        'about' => [
            'label' => 'Nosotros',
            'icon' => 'M12 12a4 4 0 100-8 4 4 0 000 8zm0 0c-3 0-6 2-6 5v3h12v-3c0-3-3-5-6-5z',
            'variants' => [
                'split' => ['label' => 'Dividido', 'content' => ['title' => 'Sobre nosotros', 'text' => 'Cuenta la historia de tu empresa.', 'image' => '', 'features' => []]],
                'centered' => ['label' => 'Centrado', 'content' => ['title' => 'Sobre nosotros', 'text' => 'Cuenta la historia de tu empresa.']],
            ],
        ],
        'team' => [
            'label' => 'Equipo',
            'icon' => 'M12 20a8 8 0 100-16 8 8 0 000 16zm0 0c2-3 4-5 6-5m-6 5c-2-3-4-5-6-5',
            'variants' => [
                'cards' => ['label' => 'Tarjetas', 'content' => ['title' => 'Nuestro equipo', 'items' => [['name' => 'Nombre Apellido', 'role' => 'Cargo', 'photo' => ''], ['name' => 'Nombre Apellido', 'role' => 'Cargo', 'photo' => '']]]],
            ],
        ],
        'testimonials' => [
            'label' => 'Testimonios',
            'icon' => 'M6 11h4V7H6v4zm8 0h4V7h-4v4zM4 15h16',
            'variants' => [
                'cards' => ['label' => 'Tarjetas', 'content' => ['title' => 'Lo que dicen nuestros clientes', 'items' => [['quote' => 'Gran experiencia trabajando con ellos.', 'author' => 'Cliente satisfecho', 'role' => 'Empresa']]]],
                'quotes' => ['label' => 'Citas', 'content' => ['title' => 'Lo que dicen nuestros clientes', 'items' => [['quote' => 'Gran experiencia trabajando con ellos.', 'author' => 'Cliente satisfecho', 'role' => 'Empresa']]]],
            ],
        ],
        'gallery' => [
            'label' => 'Galería',
            'icon' => 'M3 5h18v14H3z',
            'variants' => [
                'grid' => ['label' => 'Rejilla', 'content' => ['title' => 'Galería', 'images' => [['url' => '', 'alt' => '']]]],
            ],
        ],
        'stats' => [
            'label' => 'Cifras',
            'icon' => 'M4 20V10m6 10V4m6 16v-8m6 8H2',
            'variants' => [
                'row' => ['label' => 'Fila', 'content' => ['items' => [['value' => '10+', 'label' => 'Años de experiencia'], ['value' => '500+', 'label' => 'Clientes felices'], ['value' => '24/7', 'label' => 'Atención']]]],
            ],
        ],
        'faq' => [
            'label' => 'FAQ',
            'icon' => 'M12 6a3 3 0 11-3 3m3-3a3 3 0 013 3c0 2-3 3-3 5m-0 4h.01',
            'variants' => [
                'accordion' => ['label' => 'Acordeón', 'content' => ['title' => 'Preguntas frecuentes', 'items' => [['q' => '¿Pregunta?', 'a' => 'Respuesta.']]]],
            ],
        ],
        'cta' => [
            'label' => 'Llamada a la acción',
            'icon' => 'M13 6l4 4m0 0l-4 4m4-4H5',
            'variants' => [
                'banner' => ['label' => 'Banner', 'content' => ['title' => '¿Listo para empezar?', 'text' => 'Contáctanos hoy mismo.', 'button' => ['label' => 'Contactar', 'url' => '#contacto']]],
            ],
        ],
        'contact' => [
            'label' => 'Contacto',
            'icon' => 'M4 4h16v12H7l-3 4V4z',
            'variants' => [
                'split' => ['label' => 'Dividido', 'content' => ['title' => 'Contacto', 'subtitle' => 'Escríbenos y te responderemos pronto.', 'show_form' => true]],
                'centered' => ['label' => 'Centrado', 'content' => ['title' => 'Contacto', 'subtitle' => 'Escríbenos y te responderemos pronto.', 'show_form' => true]],
            ],
        ],
        'map' => [
            'label' => 'Mapa',
            'icon' => 'M12 21s6-5 6-10a6 6 0 10-12 0c0 5 6 10 6 10zm0-7a3 3 0 100-6 3 3 0 000 6z',
            'variants' => [
                'embed' => ['label' => 'Incrustado', 'content' => ['title' => 'Ubicación', 'embed_url' => '']],
            ],
        ],
    ],

    'templates' => [
        'modern-tech' => [
            'label' => 'Tecnológico moderno',
            'industry' => ['industria', 'tecnología', 'ingeniería'],
            'style' => 'dark',
            'description' => 'Diseño técnico, oscuro y moderno, ideal para industria y tecnología.',
            'sections' => [
                ['type' => 'navbar', 'variant' => 'default'],
                ['type' => 'hero', 'variant' => 'split'],
                ['type' => 'stats', 'variant' => 'row'],
                ['type' => 'services', 'variant' => 'cards'],
                ['type' => 'products', 'variant' => 'cards'],
                ['type' => 'about', 'variant' => 'split'],
                ['type' => 'testimonials', 'variant' => 'cards'],
                ['type' => 'faq', 'variant' => 'accordion'],
                ['type' => 'cta', 'variant' => 'banner'],
                ['type' => 'contact', 'variant' => 'split'],
                ['type' => 'map', 'variant' => 'embed'],
            ],
            'theme' => [
                'primary' => '#0ea5e9',
                'secondary' => '#6366f1',
                'background' => '#0b1220',
                'text' => '#e2e8f0',
                'muted' => '#94a3b8',
                'font' => 'Space Grotesk',
                'radius' => 'medium',
                'button_style' => 'filled',
            ],
        ],
        'minimal-business' => [
            'label' => 'Corporativo minimalista',
            'industry' => ['servicios', 'consultoría', 'profesionales'],
            'style' => 'light',
            'description' => 'Elegante, limpio y corporativo, para servicios profesionales.',
            'sections' => [
                ['type' => 'navbar', 'variant' => 'default'],
                ['type' => 'hero', 'variant' => 'minimal'],
                ['type' => 'about', 'variant' => 'centered'],
                ['type' => 'services', 'variant' => 'grid'],
                ['type' => 'stats', 'variant' => 'row'],
                ['type' => 'team', 'variant' => 'cards'],
                ['type' => 'testimonials', 'variant' => 'quotes'],
                ['type' => 'faq', 'variant' => 'accordion'],
                ['type' => 'cta', 'variant' => 'banner'],
                ['type' => 'contact', 'variant' => 'centered'],
            ],
            'theme' => [
                'primary' => '#1e3a8a',
                'secondary' => '#64748b',
                'background' => '#ffffff',
                'text' => '#0f172a',
                'muted' => '#64748b',
                'font' => 'Inter',
                'radius' => 'none',
                'button_style' => 'filled',
            ],
        ],
        'restaurant' => [
            'label' => 'Restaurante',
            'industry' => ['restaurante', 'gastronomía', 'comida'],
            'style' => 'warm',
            'description' => 'Cálido y apetitoso, pensado para restaurantes y cafeterías.',
            'sections' => [
                ['type' => 'navbar', 'variant' => 'default'],
                ['type' => 'hero', 'variant' => 'fullscreen'],
                ['type' => 'about', 'variant' => 'split'],
                ['type' => 'products', 'variant' => 'cards'],
                ['type' => 'gallery', 'variant' => 'grid'],
                ['type' => 'testimonials', 'variant' => 'cards'],
                ['type' => 'contact', 'variant' => 'split'],
                ['type' => 'map', 'variant' => 'embed'],
            ],
            'theme' => [
                'primary' => '#b45309',
                'secondary' => '#78350f',
                'background' => '#fff7ed',
                'text' => '#292524',
                'muted' => '#78716c',
                'font' => 'Playfair Display',
                'radius' => 'large',
                'button_style' => 'filled',
            ],
        ],
        'beauty-clinic' => [
            'label' => 'Clínica y estética',
            'industry' => ['clínica', 'salud', 'estética', 'belleza'],
            'style' => 'soft',
            'description' => 'Suave y confiable, ideal para clínicas, spa y estética.',
            'sections' => [
                ['type' => 'navbar', 'variant' => 'default'],
                ['type' => 'hero', 'variant' => 'split'],
                ['type' => 'services', 'variant' => 'cards'],
                ['type' => 'about', 'variant' => 'centered'],
                ['type' => 'team', 'variant' => 'cards'],
                ['type' => 'testimonials', 'variant' => 'cards'],
                ['type' => 'faq', 'variant' => 'accordion'],
                ['type' => 'contact', 'variant' => 'split'],
            ],
            'theme' => [
                'primary' => '#be185d',
                'secondary' => '#f472b6',
                'background' => '#fdf2f8',
                'text' => '#500724',
                'muted' => '#9d174d',
                'font' => 'Poppins',
                'radius' => 'full',
                'button_style' => 'filled',
            ],
        ],
        'realty' => [
            'label' => 'Inmobiliaria',
            'industry' => ['inmobiliaria', 'bienes raíces', 'propiedades'],
            'style' => 'premium',
            'description' => 'Premium y espacioso, para inmobiliarias y propiedades.',
            'sections' => [
                ['type' => 'navbar', 'variant' => 'default'],
                ['type' => 'hero', 'variant' => 'fullscreen'],
                ['type' => 'stats', 'variant' => 'row'],
                ['type' => 'products', 'variant' => 'cards'],
                ['type' => 'about', 'variant' => 'split'],
                ['type' => 'testimonials', 'variant' => 'cards'],
                ['type' => 'contact', 'variant' => 'split'],
                ['type' => 'map', 'variant' => 'embed'],
            ],
            'theme' => [
                'primary' => '#0f766e',
                'secondary' => '#134e4a',
                'background' => '#f0fdfa',
                'text' => '#042f2e',
                'muted' => '#5f6f6e',
                'font' => 'Playfair Display',
                'radius' => 'medium',
                'button_style' => 'filled',
            ],
        ],
        'startup-saas' => [
            'label' => 'Startup / SaaS',
            'industry' => ['software', 'saas', 'startup', 'app'],
            'style' => 'gradient',
            'description' => 'Moderna y vibrante, para startups y productos digitales.',
            'sections' => [
                ['type' => 'navbar', 'variant' => 'default'],
                ['type' => 'hero', 'variant' => 'centered'],
                ['type' => 'stats', 'variant' => 'row'],
                ['type' => 'services', 'variant' => 'cards'],
                ['type' => 'testimonials', 'variant' => 'cards'],
                ['type' => 'faq', 'variant' => 'accordion'],
                ['type' => 'cta', 'variant' => 'banner'],
                ['type' => 'contact', 'variant' => 'centered'],
            ],
            'theme' => [
                'primary' => '#8b5cf6',
                'secondary' => '#ec4899',
                'background' => '#ffffff',
                'text' => '#1e1b4b',
                'muted' => '#6b7280',
                'font' => 'Inter',
                'radius' => 'large',
                'button_style' => 'filled',
            ],
        ],
    ],
];
