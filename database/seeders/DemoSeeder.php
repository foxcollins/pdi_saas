<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\BusinessProfile;
use App\Models\Domain;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Knowledge\KnowledgePipelineService;
use App\Services\Site\WebsiteBuilderService;
use App\Support\TenantContext;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $plan = Plan::where('slug', 'business')->first();

        $tenant = Tenant::updateOrCreate(
            ['slug' => 'andina-hidraulica'],
            [
                'name' => 'Andina Hidráulica',
                'industry' => 'Ingeniería industrial',
                'country' => 'PE',
                'plan_id' => $plan?->id,
                'status' => 'active',
            ]
        );

        TenantContext::set($tenant->id);

        $admin = User::updateOrCreate(
            ['email' => 'admin@pdisaas.com'],
            ['name' => 'Admin Plataforma', 'password' => 'password', 'is_platform_admin' => true]
        );

        $owner = User::updateOrCreate(
            ['email' => 'demo@andina.com'],
            ['name' => 'María Fernández', 'password' => 'password']
        );

        $tenant->users()->syncWithoutDetaching([$admin->id => ['role' => 'agency'], $owner->id => ['role' => 'owner']]);

        BusinessProfile::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'name' => 'Andina Hidráulica',
                'tagline' => 'Soluciones hidráulicas para la industria',
                'description' => 'Empresa de ingeniería industrial especializada en bombas hidráulicas, mantenimiento de sistemas de bombeo, consultoría técnica y repuestos de alta calidad para minería, agricultura y construcción.',
                'industry' => 'Ingeniería industrial',
                'services' => [
                    ['icon' => 'sparkles', 'title' => 'Ingeniería y diseño', 'description' => 'Diseño de sistemas de bombeo y soluciones hidráulicas a medida.'],
                    ['icon' => 'wrench', 'title' => 'Mantenimiento y reparación', 'description' => 'Mantenimiento preventivo y correctivo de bombas industriales con garantía.'],
                    ['icon' => 'shield', 'title' => 'Repuestos originales', 'description' => 'Distribución de repuestos y componentes de las mejores marcas.'],
                    ['icon' => 'chart', 'title' => 'Consultoría técnica', 'description' => 'Asesoría en eficiencia energética y optimización de sistemas hidráulicos.'],
                ],
                'products' => [
                    ['title' => 'Bomba centrífuga serie HC', 'description' => 'Bomba centrífuga de alta eficiencia para caudales industriales.', 'price' => 'Desde USD 2,400'],
                    ['title' => 'Sistema de bombeo solar', 'description' => 'Soluciones de bombeo con energía solar para riego y abastecimiento.', 'price' => 'Desde USD 1,800'],
                    ['title' => 'Kit de repuestos estándar', 'description' => 'Kit de sellos, rodamientos y empaquetaduras para mantenimiento.', 'price' => 'Desde USD 120'],
                ],
                'branches' => [
                    ['name' => 'Sede central', 'address' => 'Av. Industrial 1240, Lima', 'phone' => '+51 999 000 111'],
                ],
                'schedule' => [
                    ['days' => 'Lunes a viernes', 'hours' => '08:00 - 18:00'],
                    ['days' => 'Sábados', 'hours' => '09:00 - 13:00'],
                ],
                'contact' => [
                    'phone' => '+51 999 000 111',
                    'whatsapp' => '51999000111',
                    'email' => 'contacto@andinahidraulica.com',
                    'address' => 'Av. Industrial 1240, Lima, Perú',
                ],
                'social' => ['facebook' => 'https://facebook.com/andinahidraulica', 'instagram' => 'https://instagram.com/andinahidraulica'],
                'faqs' => [
                    ['q' => '¿Hacen mantenimiento a domicilio?', 'a' => 'Sí, nuestros técnicos se desplazan a planta. El tiempo de respuesta depende de la ubicación del proyecto.'],
                    ['q' => '¿Qué garantía ofrecen?', 'a' => 'Todas las reparaciones incluyen 6 meses de garantía sobre la mano de obra y 12 meses sobre repuestos originales.'],
                    ['q' => '¿Pueden diseñar un sistema de bombeo a medida?', 'a' => 'Claro. Nuestro equipo de ingeniería evalúa tu requerimiento y diseña la solución óptima sin costo inicial.'],
                    ['q' => '¿Trabajan con energía solar?', 'a' => 'Sí, ofrecemos sistemas de bombeo solar para riego y abastecimiento con ingeniería completa.'],
                ],
                'team' => [
                    ['name' => 'Ing. Carlos Ramos', 'role' => 'Gerente de ingeniería'],
                    ['name' => 'María Fernández', 'role' => 'Jefa de operaciones'],
                ],
                'certifications' => [
                    'ISO 9001:2015',
                    'Certificación ISO 45001',
                ],
            ]
        );

        app(WebsiteBuilderService::class)->createSite($tenant, 'modern-tech', 'Andina Hidráulica');

        $this->seedKnowledge($tenant);

        Agent::updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'assistant'],
            [
                'name' => 'Asistente de Andina Hidráulica',
                'instructions' => 'Eres el asistente virtual de Andina Hidráulica. Responde en español, con tono profesional y cercano, ayudando a los visitantes con información sobre servicios, productos, horarios y contacto.',
                'tools' => [],
                'is_active' => true,
            ]
        );

        Domain::updateOrCreate(
            ['host' => 'demo.pdi_saas.test'],
            ['tenant_id' => $tenant->id, 'is_primary' => true, 'status' => 'verified', 'verified_at' => now()]
        );

        $this->command?->info('Tenant demo listo: demo@andina.com / password · slug: andina-hidraulica');
    }

    private function seedKnowledge(Tenant $tenant): void
    {
        $pipeline = app(KnowledgePipelineService::class);

        $text = <<<'TEXT'
        Andina Hidráulica es una empresa de ingeniería industrial especializada en bombas hidráulicas, con más de 15 años de experiencia en el mercado.
        Nuestros servicios incluyen ingeniería y diseño de sistemas de bombeo, mantenimiento preventivo y correctivo, distribución de repuestos originales y consultoría técnica.
        Todos los mantenimientos incluyen 6 meses de garantía sobre la mano de obra y 12 meses sobre los repuestos originales instalados.
        Atendemos proyectos de minería, agricultura, construcción y plantas industriales en todo el país.
        Nuestro horario de atención es de lunes a viernes de 08:00 a 18:00 horas y los sábados de 09:00 a 13:00 horas.
        El teléfono de contacto es +51 999 000 111 y el correo es contacto@andinahidraulica.com. También nos puede escribir por WhatsApp al 51999000111.
        Ofrecemos sistemas de bombeo solar para riego y abastecimiento con ingeniería completa, incluida la instalación y puesta en marcha.
        Los precios de las bombas centrífugas de la serie HC inician en USD 2,400 y los kits de repuestos estándar desde USD 120.
        La empresa está certificada bajo ISO 9001:2015 e ISO 45001, garantizando calidad y seguridad en todos los proyectos.
        Nuestra sede central se encuentra en Av. Industrial 1240, Lima, Perú.
        TEXT;

        $document = $pipeline->createFromText($tenant, 'Información general de Andina Hidráulica', $text, 'manual');
        $pipeline->process($document);
    }
}
